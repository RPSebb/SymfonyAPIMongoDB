<?php
namespace App\Controller;

use App\Document\Author as Document;
use App\Serializer\_Serializer as Serializer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;

class AuthorController extends AbstractController {

    //-----------------------CREATE-------------------------//
    #[Route('/api/author', name: 'author_create', methods: ['POST'])]
    #[OA\Tag(name: 'Author')]
    public function create(DocumentManager $dm, Request $request, ValidatorInterface $validator) : JsonResponse {

        // Try to retrieve data from Request
        // If no data was send, array will be empty
        try { $requestData = $request->toArray(); } // JSON data to array
        catch (\Exception $ex) { $requestData = $request->request->all(); } // Classic form data to array

        if(isset($requestData['id'])) { unset($requestData['id']);}

        Serializer::denormalize($requestData, $document = new Document());
        $errors = $validator->validate($document);

        if (count($errors) > 0) { return new JsonResponse(beautifyError((string)$errors), 400); }

        $dm->persist($document);
        $dm->flush();

        return new JsonResponse('Created', 201);
    }

    //-----------------------READ_ALL-------------------------//
    #[Route('/api/author', name: 'author_read_all', methods: ['GET'])]
    #[OA\Tag(name: 'Author')]
    public function readAll(DocumentManager $dm, Request $request, TagAwareCacheInterface $cachePool) : JsonResponse {

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $idCache = 'author-'.$page.'-'.$limit;

        $keys = array_keys(Serializer::normalize(new Document()));
        $params = [];

        // Retrieve parameters if they correspond Document's field
        foreach($keys as $key) {

            $value = $request->get($key, '');

            if(!empty($value)) { 

                $params[$key] = $value ; // Insert value into an Array for the QueryBuilder
                $idCache .= '-'.$key.'='.$value; // Add field to cache name
            }
        }

        $documents = $cachePool->get($idCache, 
            function(ItemInterface $item) use ($dm, $params, $page, $limit) {
                $item->tag('authorCache');
                $qb = $dm->createQueryBuilder(Document::class);
                
                foreach($params as $key => $value) {

                    $qb->field($key)->in([$value]);
                }

                $qb->limit($limit)->skip(($page - 1) * $limit);

                return Serializer::normalize($qb->getQuery()->execute(), ['groups'=>'show']);
            }
        );

        return $documents ? new JsonResponse($documents, 200) : new JsonResponse('Nothing found', 404);
    }

    //-----------------------READ_ONE-------------------------//
    #[Route('/api/author/{id}', name: 'author_read', methods: ['GET'])]
    #[OA\Tag(name: 'Author')]
    public function read(DocumentManager $dm, $id) : JsonResponse {

        $document = $dm->getRepository(Document::class)->find($id);
        $arr = Serializer::normalize($document, ['groups'=>'show']);

        return $document ? new JsonResponse($arr, 200) : new JsonResponse('Nothing found', 404);
    }

    //-----------------------UPDATE-------------------------//
    #[Route('/api/author/{id}', name: 'author_update', methods: ['PUT', 'PATCH'])]
    #[OA\Tag(name: 'Author')]
    public function update(DocumentManager $dm, $id, Request $request, ValidatorInterface $validator) : JsonResponse {

        $document = $dm->getRepository(Document::class)->find($id);
        if (!$document) { return new JsonResponse('Nothing found', 404); }

        // Try to retrieve data from Request
        // If no data was send, array will be empty
        try { $requestData = $request->toArray(); } // JSON data to array
        catch (\Exception $ex) { $requestData = $request->request->all(); } // Classic form data to array

        if(isset($requestData['id'])) { unset($requestData['id']);}
        if(empty($requestData)) { return new JsonResponse('Bad request', 400); }
        
        $documentData = Serializer::normalize($document, ['groups'=>'edit']);
        Serializer::denormalize([...$documentData, ...$requestData], $document);
        
        $errors = $validator->validate($document);
        if (count($errors) > 0) { return new JsonResponse(beautifyError((string)$errors), 400); }

        $dm->flush();

        return new JsonResponse('Updated', 200);
    }

    //-----------------------DELETE-------------------------//
    #[Route('/api/author/{id}', name: 'author_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Author')]
    public function delete(DocumentManager $dm, $id) : JsonResponse {

        $document = $dm->getRepository(Document::class)->find($id);
        if (!$document) { return new JsonResponse('Nothing found', 404); }

        $dm->remove($document);
        $dm->flush();

        return new JsonResponse('Deleted', 200);
    }
}