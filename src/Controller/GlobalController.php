<?php
namespace App\Controller;

use App\Document\Book as Book;
use App\Document\Author as Author;
use Doctrine\ODM\MongoDB\Configuration;
use MongoDB\Client;
use App\Serializer\_Serializer as Serializer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Common\Collections\ArrayCollection;

class GlobalController extends AbstractController {

    //-----------------------CREATE-------------------------//
    #[Route('/api/global/create', name: 'create_all_book_and_author', methods: ['GET'])]
    public function createAllBookAndAuthor(DocumentManager $dm) : JsonResponse {
        $this->deleteAllBook($dm);
        $book_data = json_decode(file_get_contents('../data/books.json'));
        $authors = [];

        foreach($book_data as $data) {

            $data = (array)$data;
            $book = new Book();
            Serializer::denormalize($data, $book);
            $book->setAuthor(new ArrayCollection());

            $author_info = is_array($data['author']) ? $data['author'] : array($data['author']);

            foreach($author_info as $info) {

                if(!isset($authors[$info])) {

                    $author = new Author();
                    list($surname, $name) = explode(', ', $info);
                    $author->setSurname($surname);
                    $author->setName($name);
                    $authors[$info] = $author;

                }

                $book->addAuthor($authors[$info]);
            }

            $dm->persist($book);
        }

        $dm->flush();

        return new JsonResponse('Created', 200);
    }

    //-----------------------DELETE-------------------------//
    #[Route('/api/global/delete', name: 'delete_all_book_and_author', methods: ['GET'])]
    public function deleteAllBook(DocumentManager $dm) : JsonResponse {

        $db = (new Client($_SERVER['MONGODB_URL']))->selectDatabase($_SERVER['MONGODB_DB']);
        $db->dropCollection('books');
        $db->dropCollection('authors');

        return new JsonResponse('Deleted', 200);
    }
}