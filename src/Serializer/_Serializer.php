<?php
namespace App\Serializer;

use Symfony\Component\Serializer\Serializer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;

abstract class _Serializer {

    public static function normalize($documents, array $group = []) {
        return (new Serializer([new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())))]))
            ->normalize($documents, null, $group);
    }

    public static function denormalize(?array $data, $document) {
        (new Serializer([new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())))]))
            ->denormalize($data, get_class($document), null, [AbstractNormalizer::OBJECT_TO_POPULATE => $document]);
    }
}