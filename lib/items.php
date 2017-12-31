<?php

class Items
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function getItems()
    {
        $items = [];

        foreach($this->loadItems() as $item) {
            $items[$item['item_id']] = $item;
            $items[$item['item_id']]['bundles'] = [];
        }

        foreach($this->loadBundles() as $bundle) {
            if (isset($items[$bundle['item_id']]['bundles'])) {
                $items[$bundle['item_id']]['bundles'][] = $bundle;
            }
        }

        return $items;
    }

    /**
     * @return array
     */
    private function loadItems()
    {
        return $this->db->fetchAll("SELECT * FROM items");
    }

    /**
     * @return array
     */
    private function loadBundles()
    {
        return $this->db->fetchAll("SELECT * FROM bundles");
    }

    /**
     * @param array $orders
     *
     * @return bool
     */
    public function orderItem(array $orders)
    {
        $this->db->exec("LOCK TABLE bundles WRITE");

        $bundles = [];
        foreach ($this->loadBundles() as $bundle) {
            $bundles[$bundle['bundle_id']] = $bundle['inventory'];
        }

        foreach ($orders as $order) {
            $amount = (int)$order['amount'];
            $bundleId = (int)$order['bundle_id'];

            if ($amount > $bundles[$bundleId]) {
                $this->db->exec("UNLOCK TABLE");
                return false;
            }
        }

        foreach ($orders as $order) {
            $amount = (int)$order['amount'];
            $bundleId = (int)$order['bundle_id'];

            $this->db->exec("
                UPDATE bundles
                SET
                  inventory=inventory-$amount
                WHERE bundle_id=$bundleId"
            );
        }

        $this->db->exec("UNLOCK TABLE");
        return true;
    }
}