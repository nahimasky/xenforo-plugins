<?php

namespace Sky\ComposerBlueprints\Admin\Controller;

use Sky\ComposerBlueprints\Entity\Blueprint as BlueprintEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Blueprint extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('manageComposerBlueprints');
    }

    public function actionIndex()
    {
        $blueprints = $this->repository('Sky\ComposerBlueprints:Blueprint')
            ->findActiveBlueprints()
            ->fetch();

        return $this->view('Sky\ComposerBlueprints:Blueprint\Index', 'scb_blueprint_list', [
            'blueprints' => $blueprints,
        ]);
    }

    public function actionAdd()
    {
        /** @var BlueprintEntity $blueprint */
        $blueprint = $this->em()->create('Sky\ComposerBlueprints:Blueprint');

        if ($this->isPost())
        {
            return $this->saveBlueprint($blueprint);
        }

        return $this->blueprintEditResponse($blueprint);
    }

    public function actionEdit(ParameterBag $params)
    {
        /** @var BlueprintEntity $blueprint */
        $blueprint = $this->assertRecordExists('Sky\ComposerBlueprints:Blueprint', $params->blueprint_id);

        if ($this->isPost())
        {
            return $this->saveBlueprint($blueprint);
        }

        return $this->blueprintEditResponse($blueprint);
    }

    protected function blueprintEditResponse(BlueprintEntity $blueprint)
    {
        $forums = $this->finder('XF:Node')
            ->where('node_type_id', 'Forum')
            ->order('lft')
            ->fetch();

        return $this->view('Sky\ComposerBlueprints:Blueprint\Edit', 'scb_blueprint_edit', [
            'blueprint' => $blueprint,
            'forums' => $forums,
        ]);
    }

    protected function saveBlueprint(BlueprintEntity $blueprint)
    {
        $this->assertPostOnly();

        $title = $this->filter('title', 'str');
        $description = $this->filter('description', 'str');
        $message = $this->filter('message', 'str');
        $displayOrder = $this->filter('display_order', 'uint');
        $active = $this->filter('active', 'bool');
        $contexts = array_values(array_intersect(
            $this->filter('contexts', 'array'),
            BlueprintEntity::VALID_CONTEXTS
        ));
        $nodeIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $this->filter('node_ids', 'array')
        ))));

        if ($title === '' || $message === '')
        {
            return $this->error(\XF::phrase('please_complete_required_fields'));
        }

        if (!$contexts)
        {
            return $this->error(\XF::phrase('scb_select_at_least_one_context'));
        }

        $validNodeIds = $this->getValidForumNodeIds($nodeIds);
        if (count($validNodeIds) !== count($nodeIds))
        {
            return $this->error(\XF::phrase('scb_select_valid_forums_only'));
        }

        $blueprint->bulkSet([
            'title' => $title,
            'description' => $description,
            'message' => $message,
            'contexts' => $contexts,
            'node_ids' => $validNodeIds,
            'display_order' => $displayOrder,
            'active' => $active,
        ]);
        $blueprint->save();

        return $this->redirect($this->buildLink('sky-composer-blueprints'));
    }

    protected function getValidForumNodeIds(array $nodeIds): array
    {
        if (!$nodeIds)
        {
            return [];
        }

        $forums = $this->finder('XF:Node')
            ->where('node_type_id', 'Forum')
            ->where('node_id', $nodeIds)
            ->fetch();

        $validNodeIds = [];
        foreach ($forums as $forum)
        {
            $validNodeIds[] = (int)$forum->node_id;
        }

        return $validNodeIds;
    }
}
