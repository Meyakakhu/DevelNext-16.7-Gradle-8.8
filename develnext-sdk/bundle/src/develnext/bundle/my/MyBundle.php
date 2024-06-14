<?php
namespace develnext\bundle\my;

use ide\bundle\AbstractBundle;
use ide\bundle\AbstractJarBundle;
use ide\library\IdeLibraryBundleResource;
use ide\project\Project;

/**
 * Class MyBundle
 */
class MyBundle extends AbstractJarBundle
{
    /**
     * @param IdeLibraryBundleResource $resource
     */
    public function onRegister(IdeLibraryBundleResource $resource)
    {
        parent::onRegister($resource); // TODO: Change the autogenerated stub
    }

    /**
     * @param Project $project
     * @return bool
     */
    public function isAvailable(Project $project)
    {
        return parent::isAvailable($project); // TODO: Change the autogenerated stub
    }

    /**
     * @param Project $project
     * @param AbstractBundle|null $owner
     */
    public function onAdd(Project $project, AbstractBundle $owner = null)
    {
        parent::onAdd($project, $owner); // TODO: Change the autogenerated stub
    }

    /**
     * @param Project $project
     * @param AbstractBundle|null $owner
     */
    public function onRemove(Project $project, AbstractBundle $owner = null)
    {
        parent::onRemove($project, $owner); // TODO: Change the autogenerated stub
    }
}