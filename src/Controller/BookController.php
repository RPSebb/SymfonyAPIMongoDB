<?php
namespace App\Controller;

use App\Document\Book;
use App\Document\Author;
use App\Serializer\_Serializer as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
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

class BookController extends AbstractController {

    /**
     * Create a book
     */
    #[Route('/api/book', name: 'book_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        description: 'Create a new book',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'editor', type: 'string'),
                        new OA\Property(property: 'author', type: 'array',
                            items: new OA\Items(
                                anyOf: [
                                    new OA\Property(property: 'authorId', type: 'string'),
                                    // new OA\Property(property: 'authorObject', type: 'object',
                                    //     properties: [
                                    //         new OA\Property(property: 'surname', type: 'string'),
                                    //         new OA\Property(property: 'name', type: 'string'),
                                    //     ]
                                    // )
                                ]
                            )
                        )
                    ]
                ),
                // schema: new OA\Schema(ref: new Model(type: Book::class)),
                example: [
                    [
                        'title' => 'Le Royaume',
                        'author' => [['surname' => 'Carrère','name' => 'Emmanuel']],
                        'editor' => 'POL Editeur'
                    ],
                    [
                        'title' => 'Charlotte',
                        'author' => ['62ffb4a64d60a0b44a017453'],
                        'editor' => 'Editions Gallimard'
                    ],
                ]

            )
        ]
    )]
    #[OA\Parameter(in:'query', name:'many', schema: new OA\Schema(type: 'bool'))]
    #[OA\Tag(name: 'Book')]
    #[Security(name: 'Bearer')]
    #[OA\Response(response: 201, description: 'Book created')]
    #[OA\Response(response: 400, description: 'Errors')]
    public function create(DocumentManager $dm, Request $request, ValidatorInterface $validator) : JsonResponse {

        // Try to retrieve data from Request
        // If no data was send, array will be empty
        try { $requestData = $request->toArray(); } // JSON data to array
        catch (\Exception $ex) { $requestData = $request->request->all(); } // Classic form data to array

        $createMany = $request->get('many', false);
        $errors = [];
        $created = 0;

        if($createMany) {

            foreach($requestData as $bookData) {

                $authorList = $bookData['author'];
                $bookData['author'] = new ArrayCollection();
                Serializer::denormalize($bookData, $book = new Book());
                // if(isset($requestData['id'])) { unset($requestData['id']);}

                foreach($authorList as $authorData) {

                    // check authorData type
                    // array => authorData = {surname: value, name: value}
                    // string => authorData = id
                    // then try to get author from Db
                    if(is_array($authorData)) {
                        $author = $dm->getRepository(Author::class)
                            ->findOneBy(['surname' => $authorData['surname'], 'name' => $authorData['name']]);

                        if(!isset($author)) { Serializer::denormalize($authorData, $author = new Author()); }

                        $book->addAuthor($author);

                    } else {
                        
                        $author = $dm->getRepository(Author::class)->find($authorData);
                        if(isset($author)) { $book->addAuthor($author); }

                    }
                }
                
                $bookErrors = $validator->validate($book);

                if (count($bookErrors) > 0) {

                    $book = Serializer::normalize($book, ['groups' => 'show']);
                    $book['errors'] = beautifyError((string)$bookErrors);
                    array_push($errors, $book);

                } else { $dm->persist($book); $created++;}

            }
        }

        $dm->flush();

        // Created without errors
        // Created with errors
        // Errors nothing created
        if($created > 0)
        {
            if(count($errors) > 0) {
                return new JsonResponse($errors, 201);
            } else { return new JsonResponse('Created without error', 201); }
        }

        return new JsonResponse($errors, 400);
    }

    /**
     * Retrieve all books
     */
    #[Route('/api/book', name: 'book_read_all', methods: ['GET'])]
    #[OA\Tag(name: 'Book')]
    #[Security(name: 'Bearer')]
    public function readAll(DocumentManager $dm, Request $request, TagAwareCacheInterface $cachePool) : JsonResponse {

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $idCache = 'book-'.$page.'-'.$limit;

        $keys = array_keys(Serializer::normalize(new Book()));
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
                $item->tag('bookCache');
                $qb = $dm->createQueryBuilder(Book::class);
                
                foreach($params as $key => $value) {

                    $qb->field($key)->in([$value]);
                }

                $qb->limit($limit)->skip(($page - 1) * $limit);

                return Serializer::normalize($qb->getQuery()->execute(), ['groups'=>'show']);
            }
        );

        return $documents ? new JsonResponse($documents, 200) : new JsonResponse('Nothing found', 404);
    }


    /**
     * Retrieve one book by id
     */
    #[Route('/api/book/{id}', name: 'book_read', methods: ['GET'])]
    #[OA\Tag(name: 'Book')]
    #[Security(name: 'Bearer')]
    public function read(DocumentManager $dm, $id) : JsonResponse {

        $book = Serializer::normalize($dm->getRepository(Book::class)->find($id), ['groups'=>'show']);

        return $book ? new JsonResponse($book, 200) : new JsonResponse('Nothing found', 404);
    }

    /**
     * Update one book by id
     */
    #[Route('/api/book/{id}', name: 'book_update', methods: ['PUT', 'PATCH'])]
    #[OA\Tag(name: 'Book')]
    #[Security(name: 'Bearer')]
    public function update(DocumentManager $dm, $id, Request $request, ValidatorInterface $validator) : JsonResponse {

        $book = $dm->getRepository(Book::class)->find($id);
        if (!$book) { return new JsonResponse('Nothing found', 404); }

        // Try to retrieve data from Request
        // If no data was send, array will be empty
        try { $requestData = $request->toArray(); } // JSON data to array
        catch (\Exception $ex) { $requestData = $request->request->all(); } // Classic form data to array

        if(isset($requestData['id'])) { unset($requestData['id']);}
        if(empty($requestData)) { return new JsonResponse('Bad request', 400); }
        
        $bookData = Serializer::normalize($book, ['groups'=>'edit']);
        Serializer::denormalize([...$bookData, ...$requestData], $book);
        
        $errors = $validator->validate($book);
        if (count($errors) > 0) { return new JsonResponse(beautifyError((string)$errors), 400); }

        $dm->flush();

        return new JsonResponse('Updated', 200);
    }

    /**
     * Delete one book by id
     */
    #[Route('/api/book/{id}', name: 'book_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Book')]
    #[Security(name: 'Bearer')]
    public function delete(DocumentManager $dm, $id) : JsonResponse {

        $book = $dm->getRepository(Book::class)->find($id);
        if (!$book) { return new JsonResponse('Nothing found', 404); }

        $dm->remove($book);
        $dm->flush();

        return new JsonResponse('Deleted', 200);
    }
}