<?php
namespace App\Controller\Stations\Profile;

use App\Form;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class EditController
{
    /** @var Form\StationForm */
    protected $station_form;

    /**
     * @param Form\StationForm $station_form
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(Form\StationForm $station_form)
    {
        $this->station_form = $station_form;
    }

    public function __invoke(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();

        if (false !== $this->station_form->process($request, $station)) {
            return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/profile/edit', [
            'form' => $this->station_form,
        ]);
    }
}
