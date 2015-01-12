<?php

namespace App\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;
use App\Model\Order;

/**
 * Class OrdersController
 * @package App\Controller
 *
 * @SWG\Resource(
 *     apiVersion="1",
 *     basePath="/",
 *     resourcePath="/orders"
 * )
 */
class OrdersController implements ControllerProviderInterface
{
    const DATA_FILE = '/../../../web/data.json';

    /**
     * @var Order[]
     */
    protected $data = [];

    /**
     * Object constructor
     */
    public function __construct()
    {
        $this->loadData();
    }

    /**
     * @param Application $app
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

        // setup routes
        $controller->get('/orders', 'App\Controller\OrdersController::get');
        $controller->get('/orders/{id}', 'App\Controller\OrdersController::get')->assert('id', '\d+');
        $controller->post('/orders', 'App\Controller\OrdersController::post');
        $controller->delete('/orders/{id}', 'App\Controller\OrdersController::delete')->assert('id', '\d+');
        $controller->put('/orders/{id}', 'App\Controller\OrdersController::put')->assert('id', '\d+');

        return $controller;
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @SWG\Api(
     *   path="/v1/orders",
     *   @SWG\Operation(
     *      summary="Get list of orders",
     *      method="GET",
     *      type="array[Order]",
     *      @SWG\Parameter(
     *          name="q",
     *          description="Search query",
     *          paramType="query",
     *          required=false,
     *          allowMultiple=false,
     *          type="string"
     *      )
     *   )
     * )
     *
     * @SWG\Api(
     *   path="/v1/orders/{id}",
     *   @SWG\Operation(
     *      summary="Retrieves an order by ID",
     *      method="GET",
     *      type="Order",
     *      @SWG\Parameter(
     *          name="id",
     *          description="ID of the order to be fetched",
     *          paramType="path",
     *          required=true,
     *          allowMultiple=false,
     *          type="number"
     *      ),
     *      @SWG\ResponseMessage(code=404, message="Could not locate Order")
     *   )
     * )
     */
    public function get(Application $app, Request $request, $id = null)
    {
        if ($id) {
            if (array_key_exists($id, $this->data)) {
                return $app->json($this->data[$id]);
            }

            // invalid key
            throw new NotFoundHttpException('Could not locate Order');
        }

        // get data
        $data = array_values($this->data);

        // filter it, if requested
        if (($q = trim($request->get('q')))) {
            foreach ($data as $orderIdx => $order) {
                if (false === stripos($order->name, $q)) {
                    unset($data[$orderIdx]);
                }
            }
        }

        return $app->json($data);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @SWG\Api(
     *   path="/v1/orders",
     *   @SWG\Operation(
     *      summary="Creates a new order",
     *      method="POST",
     *      type="Order",
     *      @SWG\Parameter(
     *          name="body",
     *          description="Order object",
     *          paramType="body",
     *          required=true,
     *          allowMultiple=false,
     *          type="Order"
     *      ),
     *      @SWG\ResponseMessage(code=400, message="Invalid data")
     *   )
     * )
     */
    public function post(Application $app, Request $request)
    {
        if (($data = json_decode($request->getContent()))) {
            if (trim($data->name)) {
                // get next available id
                $id = max(array_keys($this->data)) + 1;

                // add new entry
                $order = new Order();
                $order->id = $id;
                $order->name = trim($data->name);
                $this->data[$id] = $order;

                // save data
                $this->saveData();

                // return data
                return $app->json($this->data[$id], Response::HTTP_CREATED);
            }
        }

        throw new BadRequestHttpException('Invalid data');
    }

    /**
     * @param Application $app
     * @param $id
     * @return Response
     *
     * @SWG\Api(
     *   path="/v1/orders/{id}",
     *   @SWG\Operation(
     *      summary="Deletes an order by ID",
     *      method="DELETE",
     *      @SWG\Parameter(
     *          name="id",
     *          description="ID of the order to be deleted",
     *          paramType="path",
     *          required=true,
     *          allowMultiple=false,
     *          type="number"
     *      ),
     *      @SWG\ResponseMessage(code=404, message="Could not locate Order")
     *   )
     * )
     */
    public function delete(Application $app, $id)
    {
        if (array_key_exists($id, $this->data)) {
            // remove entry
            unset($this->data[$id]);

            // save data
            $this->saveData();

            // return blank response
            return new Response();
        }

        // invalid key
        throw new NotFoundHttpException('Could not locate Order');
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return Response
     *
     * @SWG\Api(
     *   path="/v1/orders/{id}",
     *   @SWG\Operation(
     *      summary="Updates an order by ID",
     *      method="PUT",
     *      @SWG\Parameter(
     *          name="id",
     *          description="ID of the order to be updated",
     *          paramType="path",
     *          required=true,
     *          allowMultiple=false,
     *          type="number"
     *      ),
     *      @SWG\Parameter(
     *          name="body",
     *          description="Order object",
     *          paramType="body",
     *          required=true,
     *          allowMultiple=false,
     *          type="Order"
     *      ),
     *      @SWG\ResponseMessage(code=400, message="Invalid data"),
     *      @SWG\ResponseMessage(code=404, message="Could not locate Order")
     *   )
     * )
     */
    public function put(Application $app, Request $request, $id)
    {
        if (array_key_exists($id, $this->data)) {
            if (($data = json_decode($request->getContent()))) {
                if (trim($data->name)) {
                    // edit entry
                    $this->data[$id]->name = trim($data->name);

                    // save data
                    $this->saveData();

                    // return data
                    return $app->json($this->data[$id]);
                }
            }

            throw new BadRequestHttpException('Invalid data');
        }

        // invalid key
        throw new NotFoundHttpException('Could not locate Order');
    }

    /**
     * Load data from disk
     *
     * @return $this
     */
    protected function loadData()
    {
        if (($data = file_get_contents(__DIR__ . self::DATA_FILE))) {
            if (($data = unserialize($data))) {
                $this->data = $data;
            }
        }

        return $this;
    }

    /**
     * Save data to disk
     *
     * @return $this
     */
    protected function saveData()
    {
        file_put_contents(__DIR__ . self::DATA_FILE, serialize($this->data));

        return $this;
    }
}
