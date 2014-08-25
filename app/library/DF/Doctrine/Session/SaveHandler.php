<?php
namespace DF\Doctrine\Session;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use \Entity\Session as Record;

/**
 * Manages sessions storage through \Doctrine\ORM\EntityManager
 */
class SaveHandler implements \SessionHandlerInterface
{
    public static function register(EntityManager $em)
    {
        $sh = new self($em);
        $result = session_set_save_handler($sh, true);

        if (!$result)
            throw new \DF\Exception('Session handler could not be registered!');
    }

    /**
     * @var EntityManager
     */
    protected $_em;

    /**
     * @var \Entity\Session
     */
    protected $_session;

    /**
     * @var int
     */
    protected $_sessionLifetime;

    /**
     * @var string
     */
    protected $_sessionSavePath;

    /**
     * @var string
     */
    protected $_sessionName;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
    }

    public function __destruct()
    {
        \Zend_Session::writeClose();
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $sid
     * @return bool
     */
    public function destroy($sid)
    {
        $session = $this->_getSessionEntity($sid);

        if($this->_em->getUnitOfWork()->getEntityState($session) === UnitOfWork::STATE_MANAGED)
        {
            $this->_em->remove($session);
            $this->_em->flush();

            $session = $this->_getSessionEntity();
        }

        return true;
    }

    /**
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('Entity\Session', 's')
            ->where($qb->expr()->lt('s.expires', ':expires'))
            ->setParameter('expires', time(), Type::INTEGER)
            ->getQuery()
            ->execute();

        return true;
    }

    /**
     * @return bool
     */
    public function open($save_path, $name)
    {
        $this->_sessionSavePath = $save_path;
        $this->_sessionName     = $name;

        return true;
    }

    /**
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $session = $this->_getSessionEntity($id);

        if ($session->isValid())
            return $session->data;
        else
            $this->destroy($id);

        return '';
    }

    public function write($id, $data)
    {
        $session = $this->_getSessionEntity($id);

        $session->lifetime = ($this->_sessionLifetime ? $this->_sessionLifetime : ini_get('session.gc_maxlifetime'));
        $session->last_modified = time();
        $session->data = $data;

        if($this->_em->getUnitOfWork()->getEntityState($session, UnitOfWork::STATE_NEW) !== UnitOfWork::STATE_MANAGED)
        {
            $this->_em->persist($session);
        }

        $this->_em->flush();

        return true;
    }

    /**
     * @return Record
     */
    public function getSession()
    {
        return $this->_getSessionEntity();
    }

    /**
     * @param string $sid
     * @return Record
     */
    protected function _getSessionEntity($sid = null)
    {
        if($sid !== null)
        {
            if($this->_session instanceof Record && $this->_session->id !== $sid)
            {
                $this->_em->remove($this->_session);
                $this->_session = null;
            }

            if(!($this->_session instanceof Record))
            {
                $this->_session = Record::find($sid);
            }

            if(!($this->_session instanceof Record))
            {
                $this->_session = new Record;
                $this->_session->id = $sid;
            }
        }

        return $this->_session;
    }
}