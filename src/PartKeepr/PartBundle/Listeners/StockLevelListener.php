<?php
namespace PartKeepr\PartBundle\Listeners;

use Doctrine\ORM\Event\OnFlushEventArgs;
use PartKeepr\PartBundle\Entity\Part;
use PartKeepr\StockBundle\Entity\StockEntry;
use Symfony\Component\DependencyInjection\ContainerAware;

class StockLevelListener extends ContainerAware
{
    /**
     * Updates the child category paths when their parent name has changed.
     *
     * @param OnFlushEventArgs $eventArgs The event arguments as given by Doctrine
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

        $parts = array();

        foreach ($uow->getScheduledEntityInsertions() as $updated) {
            if ($updated instanceof StockEntry) {
                if (!in_array($updated->getPart(), $parts)) {
                    $parts[] = $updated->getPart();
                }
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $updated) {
            if ($updated instanceof StockEntry) {
                if (!in_array($updated->getPart(), $parts)) {
                    $parts[] = $updated->getPart();
                }
            }
        }

        foreach ($parts as $part) {
            $this->updateStockLevel($part, $eventArgs);
        }
    }

    /**
     * Updates the stock level for a specific part
     *
     * @param Part             $part      The part to update
     * @param OnFlushEventArgs $eventArgs The event arguments
     */
    protected function updateStockLevel(Part $part, OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();

        $sum = 0;

        foreach ($part->getStockLevels() as $stockLevel) {
            $sum +=  $stockLevel->getStockLevel();
        }

        $part->setStockLevel($sum);

        $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $entityManager->getClassMetadata(get_class($part)),
            $part
        );
    }
}