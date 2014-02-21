<?php
/**
 * DF MSSQL Adapter Class
 */

namespace DF\Doctrine\Platform;

use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Index,
    Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class Mssql extends \Doctrine\DBAL\Platforms\SQLServer2008Platform
{
	/**
     * @override
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
		static $schema;
		// Initialize schema.
		if ($schema === NULL)
		{
			$em = \Zend_Registry::get('em');
			$sm = $em->getConnection()->getSchemaManager();
			$schema = $sm->createSchema();
		}
		
		$current_table = $schema->getTable($diff->name);
		
		$sql = array();
        $queryParts = array();
        
        if ($diff->newName !== false) {
            $queryParts[] = 'RENAME TO ' . $diff->newName;
        }

        foreach ($diff->addedColumns AS $fieldName => $column) {
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());
        }

        foreach ($diff->removedColumns AS $column) {
            $queryParts[] = 'DROP COLUMN ' . $column->getQuotedName($this);
        }
            
		foreach ($diff->changedColumns AS $columnDiff)
		{
			// Check for columns that aren't really different (custom types).
			$current_column = $current_table->getColumn($columnDiff->oldColumnName)->toArray();
			$column = $columnDiff->column->toArray();
			
			$current_def = $current_column['type']->getSqlDeclaration($current_column, $this);
			$new_def = $column['type']->getSqlDeclaration($column, $this);
			
			if ($new_def != $current_def)
				$queryParts[] = 'ALTER COLUMN '.$columnDiff->oldColumnName.' '.$new_def;
        }
        
        foreach ($diff->renamedColumns AS $oldColumnName => $column) {
			$sql[] = "EXEC sp_rename @objname = '".$diff->name.".".$oldColumnName."', @newname = '".$column->getQuotedName($this)."', @objtype = 'COLUMN'";
        }

        foreach ($queryParts as $query) {
            $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . $query;
        }

        $sql = array_merge($sql, $this->_getAlterTableIndexForeignKeySQL($diff));
        return $sql;
    }
    
    public function getBooleanTypeDeclarationSQL(array $field)
    {
        return 'SMALLINT';
    }
}