<?php
namespace App\Controller\Traits;

use App\Exception\Validation;
use Psr\Http\Message\ResponseInterface as Response;
use Azura\Http\Request;
use AzuraForms\Field\AbstractField;
use AzuraForms\Form;
use FastRoute\Dispatcher;
use Symfony\Component\Validator\ConstraintViolation;

trait ApiProxyTrait
{
    public function handleFormSubmission(Form $form, Request $request, $route_name, $route_method = 'GET', $router_args = []): bool
    {
        if ($request->isPost() && $form->isValid($request->getParsedBody())) {
            try {
                $resp = $this->proxyRequest($request, $route_name, $route_method, $router_args);
                return true;
            } catch(Validation $e) {
                foreach($e->getDetailedErrors() as $error) {
                    /** @var ConstraintViolation $error */
                    $field_name = $error->getPropertyPath();

                    if ($form->hasField($field_name)) {
                        /** @var AbstractField $field */
                        $field = $form->getField($field_name);
                        $field->addError($error->getMessage());
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $route_name
     * @param string $route_method
     * @param array $router_args
     * @return array|null
     * @throws \Azura\Exception
     */
    public function proxyRequest(Request $request, $route_name, $route_method = 'GET', $router_args = []): ?array
    {
        $router = $request->getRouter();

        $route_uri = $router->named($route_name, $router_args);

        $request = $request
            ->withUri($route_uri)
            ->withMethod($route_method)
            ->withHeader('Accept', 'application/json');

        $route_info = $router->dispatch($request);

        if ($route_info[0] !== Dispatcher::FOUND) {
            throw new \App\Exception('Dispatcher error: route not found.');
        }

        $route = $router->lookupRoute($route_info[1]);
        $response = $route->run($request, new Response);

        if (200 === $response->getStatusCode()) {
            $body_contents = (string)$response->getBody();

            return json_decode($body_contents, true);
        }

        return null;
    }
}
