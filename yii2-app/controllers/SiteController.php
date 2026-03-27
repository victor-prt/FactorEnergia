<?php

namespace app\controllers;

use yii\web\Controller;

class SiteController extends Controller
{
    public function actions(): array
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    public function actionIndex(): string
    {
        $this->layout = 'main';
        return $this->render('index');
    }
}
