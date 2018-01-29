<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller
{
    /**
     * @Route(
     *     "/products/{limit}/{offset}",
     *     requirements={
     *         "offset": "\d+",
     *         "limit": "\d+"
     *     },
     *     defaults={"offset"=0, "limit"=25},
     *     name="get_products",
     *     methods="GET",
     * )
     */
    public function index($limit, $offset)
    {
        $repository = $this->getDoctrine()->getRepository(Product::class);

        $products = $repository->findBy([], null, $limit, $offset);

        $serializer = $this->get('serializer');
        $jsonContent = $serializer->serialize($products, 'json');

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/product", name="add_product", methods="POST")
     */
    public function add(Request $request)
    {
        $content = $request->getContent();
        $serializer = $this->get('serializer');

        try {
            $product = $serializer->deserialize($content, Product::class, 'json');
        } catch (\Exception $ex) {
            return $this->json([], Response::HTTP_BAD_REQUEST);
        }

        $badRequest = false;

        if (!in_array($product->getType(), array(Product::FIRST_TYPE, Product::SECOND_TYPE))) {
            $badRequest = true;
        } elseif ($product->getType() === Product::FIRST_TYPE && (empty($product->getColor()) || empty($product->getTexture()))) {
            $badRequest = true;
        } elseif ($product->getType() === Product::SECOND_TYPE && (empty($product->getHeight()) || empty($product->getWidth()))) {
            $badRequest = true;
        }

        if ($badRequest) {
            return $this->json([], Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();

        $em->persist($product);

        $em->flush();

        return $this->json($product, Response::HTTP_CREATED);
    }
}