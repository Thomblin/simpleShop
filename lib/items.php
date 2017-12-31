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
}