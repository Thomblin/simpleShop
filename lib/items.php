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
            $items[$item['item_id']]['option_groups'] = [];
        }

        foreach($this->loadBundles() as $bundle) {
            if (isset($items[$bundle['item_id']]['bundles'])) {
                $items[$bundle['item_id']]['bundles'][] = $bundle;
            }
        }

        // Load and organize options by item and option group
        $itemOptions = $this->loadItemOptions();
        foreach($items as &$item) {
            $itemId = $item['item_id'];
            if (isset($itemOptions[$itemId])) {
                $item['option_groups'] = $itemOptions[$itemId];
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
        // Lock both tables since inventory may be on bundle_options or bundles
        $this->db->exec("LOCK TABLES bundles WRITE, bundle_options WRITE");

        // Load current inventories
        $bundleInventories = [];
        foreach ($this->loadBundles() as $bundle) {
            $bundleInventories[$bundle['bundle_id']] = (int)$bundle['inventory'];
        }

        $bundleOptionInventories = [];
        foreach ($this->db->fetchAll("SELECT bundle_option_id, bundle_id, inventory FROM bundle_options") as $row) {
            $bundleOptionInventories[(int)$row['bundle_option_id']] = [
                'bundle_id' => (int)$row['bundle_id'],
                'inventory' => is_null($row['inventory']) ? null : (int)$row['inventory']
            ];
        }

        // Validate availability first
        foreach ($orders as $order) {
            $amount = (int)$order['amount'];
            $bundleOptionId = isset($order['bundle_option_id']) ? (int)$order['bundle_option_id'] : 0;
            $bundleId = isset($order['bundle_id']) ? (int)$order['bundle_id'] : 0;

            if ($bundleOptionId && isset($bundleOptionInventories[$bundleOptionId]) && $bundleOptionInventories[$bundleOptionId]['inventory'] !== null) {
                if ($amount > $bundleOptionInventories[$bundleOptionId]['inventory']) {
                    $this->db->exec("UNLOCK TABLES");
                    return false;
                }
            } else {
                // Fallback to bundle inventory
                if (empty($bundleId) && $bundleOptionId && isset($bundleOptionInventories[$bundleOptionId])) {
                    $bundleId = $bundleOptionInventories[$bundleOptionId]['bundle_id'];
                }
                if (!isset($bundleInventories[$bundleId]) || $amount > $bundleInventories[$bundleId]) {
                    $this->db->exec("UNLOCK TABLES");
                    return false;
                }
            }
        }

        // Perform updates
        foreach ($orders as $order) {
            $amount = (int)$order['amount'];
            $bundleOptionId = isset($order['bundle_option_id']) ? (int)$order['bundle_option_id'] : 0;
            $bundleId = isset($order['bundle_id']) ? (int)$order['bundle_id'] : 0;

            if ($bundleOptionId && isset($bundleOptionInventories[$bundleOptionId]) && $bundleOptionInventories[$bundleOptionId]['inventory'] !== null) {
                $this->db->exec("UPDATE bundle_options SET inventory=inventory-$amount WHERE bundle_option_id=$bundleOptionId");
            } else {
                if (empty($bundleId) && $bundleOptionId && isset($bundleOptionInventories[$bundleOptionId])) {
                    $bundleId = $bundleOptionInventories[$bundleOptionId]['bundle_id'];
                }
                $this->db->exec("UPDATE bundles SET inventory=inventory-$amount WHERE bundle_id=$bundleId");
            }
        }

        $this->db->exec("UNLOCK TABLES");
        return true;
    }

    /**
     * Load all options organized by item and option group
     * @return array
     */
    private function loadItemOptions()
    {
        $result = [];

        $query = "
            SELECT
                i.item_id,
                og.option_group_id,
                og.name as group_name,
                og.display_order as group_order,
                o.option_id,
                o.name as option_name,
                o.description as option_description,
                o.display_order as option_order,
                b.bundle_id,
                b.name as bundle_name,
                bo.bundle_option_id,
                COALESCE(bo.price, b.price) AS price,
                COALESCE(bo.min_count, b.min_count) AS min_count,
                COALESCE(bo.max_count, b.max_count) AS max_count,
                COALESCE(bo.inventory, b.inventory) AS inventory
            FROM items i
            JOIN bundles b ON i.item_id = b.item_id
            JOIN bundle_options bo ON b.bundle_id = bo.bundle_id
            JOIN options o ON bo.option_id = o.option_id
            JOIN option_groups og ON o.option_group_id = og.option_group_id
            ORDER BY i.item_id, og.display_order, o.display_order
        ";

        foreach($this->db->fetchAll($query) as $row) {
            $itemId = $row['item_id'];
            $groupId = $row['option_group_id'];

            if (!isset($result[$itemId])) {
                $result[$itemId] = [];
            }

            if (!isset($result[$itemId][$groupId])) {
                $result[$itemId][$groupId] = [
                    'group_id' => $groupId,
                    'group_name' => $row['group_name'],
                    'options' => []
                ];
            }

            $result[$itemId][$groupId]['options'][] = [
                'option_id' => $row['option_id'],
                'option_name' => $row['option_name'],
                'option_description' => $row['option_description'],
                'bundle_id' => $row['bundle_id'],
                'bundle_name' => $row['bundle_name'],
                'bundle_option_id' => $row['bundle_option_id'],
                'price' => $row['price'],
                'min_count' => $row['min_count'],
                'max_count' => $row['max_count'],
                'inventory' => $row['inventory']
            ];
        }

        return $result;
    }
}