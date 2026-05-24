<?php

namespace App\Services;
use Iqbalatma\LaravelServiceRepo\BaseService;
use Iqbalatma\LaravelServiceRepo\Attributes\ServiceRepository;

//#[ServiceRepository()]
class AIService extends BaseService
{
    public function generate($topic)
    {
        return [
            'topic' => $topic,
            'exercises' => [
                'Exercice 1...',
                'Exercice 2...'
            ],
            'arabic_solution' => 'شرح بالعربية...',
            'sentences' => [
                'Sentence 1',
                'Sentence 2'
            ],
            'questions' => [
                'question 1',
                'question 2',
                'question 3',
                'question 4',
                'question 5'
            ],
            'answers' => [
                'answer 1',
                'answer 2',
                'answer 3',
                'answer 4',
                'answer 5'
            ]
        ];
    }
}
