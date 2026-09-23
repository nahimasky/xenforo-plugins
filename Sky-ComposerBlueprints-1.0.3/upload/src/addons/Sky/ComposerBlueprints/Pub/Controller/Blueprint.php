<?php

namespace Sky\ComposerBlueprints\Pub\Controller;

use Sky\ComposerBlueprints\Service\BlueprintAvailability;
use XF\Pub\Controller\AbstractController;

class Blueprint extends AbstractController
{
    public function actionList()
    {
        $context = $this->filter('context', 'str');
        $nodeId = $this->filter('node_id', 'uint');
        $threadId = $this->filter('thread_id', 'uint');

        /** @var BlueprintAvailability $availability */
        $availability = $this->service('Sky\ComposerBlueprints:BlueprintAvailability');
        $blueprints = $availability->getAvailableBlueprints($context, $nodeId, $threadId);

        $publicBlueprints = [];
        foreach ($blueprints as $blueprint)
        {
            $publicBlueprints[] = [
                'title' => $blueprint->title,
                'description' => $blueprint->description,
                'message' => $blueprint->message,
                'editor_html' => $this->app->bbCode()->render($blueprint->message, 'editorHtml', 'editor', null),
            ];
        }

        return $this->view('Sky\ComposerBlueprints:Blueprint\List', 'scb_blueprint_public_list', [
            'blueprints' => $publicBlueprints,
        ]);
    }
}
