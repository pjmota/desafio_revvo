<?php

namespace App\Controllers;

use App\Repositories\SlideRepository;
use App\Repositories\CourseRepository;

class PublicController
{
    private SlideRepository $slideRepo;
    private CourseRepository $courseRepo;

    public function __construct()
    {
        $this->slideRepo = new SlideRepository();
        $this->courseRepo = new CourseRepository();
    }

    /**
     * Carrega dados da homepage (slides e cursos) com tratamento de fallback.
     * Retorna um array associativo com chaves: db_error, slides, cursosDb, cursos, isCursosFallback.
     */
    public function home(): array
    {
        $db_error = null;
        $slides = [];
        $cursosDb = [];
        $cursos = [];
        $isCursosFallback = true;

        try {
            // Busca via repositórios; caso falhe, capturamos e aplicamos fallback
            $slides = $this->slideRepo->listAll();
            // Limita a 5 como no layout original
            if (is_array($slides)) {
                $slides = array_slice($slides, 0, 5);
            } else {
                $slides = [];
            }
            $cursosDb = $this->courseRepo->listAll();
        } catch (\Throwable $e) {
            $db_error = 'Erro ao carregar dados: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        // Fallback para conteúdo estático se nenhum dado for retornado
        if (empty($slides)) {
            $slides = [
                [
                    'titulo' => 'LOREM IPSUM',
                    'descricao' => 'Aenean lacinia bibendum nulla sed consectetur. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.',
                    'link' => '#',
                    'imagem' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1600&h=600&fit=crop'
                ],
                [
                    'titulo' => 'LOREM IPSUM',
                    'descricao' => 'Aenean lacinia bibendum nulla sed consectetur. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.',
                    'link' => '#',
                    'imagem' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1600&h=600&fit=crop'
                ],
                [
                    'titulo' => 'LOREM IPSUM',
                    'descricao' => 'Aenean lacinia bibendum nulla sed consectetur. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.',
                    'link' => '#',
                    'imagem' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=1600&h=600&fit=crop'
                ],
            ];
        }

        if (empty($cursos)) {
            $isCursosFallback = true;
            $cursos = [
                [
                    'titulo' => 'PELLENTESQUE MALESUADA',
                    'descricao' => 'Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue.',
                    'link' => '#',
                    'imagem' => '/assets/uploads/cards.jpg',
                    'novo' => false
                ],
                [
                    'titulo' => 'PELLENTESQUE MALESUADA',
                    'descricao' => 'Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue.',
                    'link' => '#',
                    'imagem' => '/assets/uploads/cards.jpg',
                    'novo' => true
                ],
                [
                    'titulo' => 'PELLENTESQUE MALESUADA',
                    'descricao' => 'Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue.',
                    'link' => '#',
                    'imagem' => '/assets/uploads/cards.jpg',
                    'novo' => false
                ],
            ];
        }

        return [
            'db_error' => $db_error,
            'slides' => $slides,
            'cursosDb' => $cursosDb,
            'cursos' => $cursos,
            'isCursosFallback' => $isCursosFallback,
        ];
    }
}