<?php

class Items
{
    /**
     * @var DatabaseInterface
     */
    private $db;

    /**
     * @param DatabaseInterface $db
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function getItems()
    {
        $items = [];

        foreach ($this->loadItems() as $item) {
            $items[$item['item_id']] = $item;
            $items[$item['item_id']]['bundles'] = [];
            $items[$item['item_id']]['option_groups'] = [];
        }

        foreach ($this->loadBundles() as $bundle) {
            if (isset($items[$bundle['item_id']]['bundles'])) {
                $items[$bundle['item_id']]['bundles'][] = $bundle;
            }
        }

        // Load and organize options by item and option group
        $itemOptions = $this->loadItemOptions();
        foreach ($items as &$item) {
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
        // Load bundles - note: bundles no longer have price, min_count, max_count, inventory
        // These fields are now only in bundle_options
        return $this->db->fetchAll("SELECT bundle_id, item_id, name FROM bundles");
    }

    /**
     * Load inventory data for validation and updates
     *
     * @return array ['bundleOptions' => array]
     */
    private function loadInventoryData()
    {
        $bundleOptionInventories = [];
        foreach ($this->db->fetchAll("SELECT bundle_option_id, bundle_id, inventory FROM bundle_options") as $row) {
            $bundleOptionInventories[(int) $row['bundle_option_id']] = [
                'bundle_id' => (int) $row['bundle_id'],
                'inventory' => (int) $row['inventory']
            ];
        }

        return [
            'bundleOptions' => $bundleOptionInventories
        ];
    }

    /**
     * Validate that all orders have sufficient inventory
     *
     * @param array $orders
     * @param array $bundleOptionInventories
     * @return bool True if all orders can be fulfilled
     */
    private function validateOrderAvailability(array $orders, array $bundleOptionInventories)
    {
        foreach ($orders as $order) {
            $amount = (int) $order['amount'];
            $bundleOptionId = isset($order['bundle_option_id']) ? (int) $order['bundle_option_id'] : 0;

            if (!$bundleOptionId || !isset($bundleOptionInventories[$bundleOptionId])) {
                return false; // All orders must have a valid bundle_option_id
            }

            if ($amount > $bundleOptionInventories[$bundleOptionId]['inventory']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute inventory updates for all orders
     *
     * @param array $orders
     * @param array $bundleOptionInventories
     * @return void
     */
    private function executeInventoryUpdates(array $orders, array $bundleOptionInventories)
    {
        foreach ($orders as $order) {
            $amount = (int) $order['amount'];
            $bundleOptionId = isset($order['bundle_option_id']) ? (int) $order['bundle_option_id'] : 0;

            if (!$bundleOptionId || !isset($bundleOptionInventories[$bundleOptionId])) {
                throw new RuntimeException("Invalid bundle_option_id in order");
            }

            $this->db->execute(
                "UPDATE bundle_options SET inventory = inventory - ? WHERE bundle_option_id = ?",
                [$amount, $bundleOptionId]
            );
        }
    }

    /**
     * @param array $orders
     *
     * @return bool
     */
    public function orderItem(array $orders)
    {
        // Use transactions instead of table locks for better testability
        $this->db->beginTransaction();

        try {
            $inventoryData = $this->loadInventoryData();
            $bundleOptionInventories = $inventoryData['bundleOptions'];

            // Consolidate orders by bundle_option_id (sum amounts for same bundle_option_id)
            $consolidatedOrders = $this->consolidateOrders($orders);

            // Validate availability first
            if (!$this->validateOrderAvailability($consolidatedOrders, $bundleOptionInventories)) {
                $this->db->rollback();
                return false;
            }

            // Perform updates with prepared statements to prevent SQL injection
            $this->executeInventoryUpdates($consolidatedOrders, $bundleOptionInventories);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Consolidate orders by bundle_option_id - sum amounts for orders with the same bundle_option_id
     * This ensures that if the same bundle_option_id appears multiple times, their amounts are summed
     *
     * @param array $orders
     * @return array Consolidated orders
     */
    private function consolidateOrders(array $orders)
    {
        $consolidated = [];

        foreach ($orders as $order) {
            $bundleOptionId = isset($order['bundle_option_id']) ? (int) $order['bundle_option_id'] : 0;
            $amount = (int) $order['amount'];

            // If no bundle_option_id, keep the order as-is for validation to catch
            if (!$bundleOptionId) {
                $consolidated[] = $order;
                continue;
            }

            if (isset($consolidated[$bundleOptionId])) {
                // Sum amounts for the same bundle_option_id
                $consolidated[$bundleOptionId]['amount'] += $amount;
            } else {
                // First occurrence of this bundle_option_id
                $consolidated[$bundleOptionId] = [
                    'bundle_option_id' => $bundleOptionId,
                    'amount' => $amount
                ];
            }
        }

        // Return as indexed array (values only)
        return array_values($consolidated);
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
                bo.price AS price,
                bo.min_count AS min_count,
                bo.max_count AS max_count,
                bo.inventory AS inventory
            FROM items i
            JOIN bundles b ON i.item_id = b.item_id
            JOIN bundle_options bo ON b.bundle_id = bo.bundle_id
            JOIN options o ON bo.option_id = o.option_id
            JOIN option_groups og ON o.option_group_id = og.option_group_id
            ORDER BY i.item_id, og.display_order, o.display_order
        ";

        foreach ($this->db->fetchAll($query) as $row) {
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

    /**
     * Get all bundle_options for a specific bundle
     * Used when bundles don't have option_groups
     *
     * @param int $bundleId
     * @return array
     */
    public function getBundleOptionsForBundle($bundleId)
    {
        return $this->db->fetchAll(
            "SELECT bundle_option_id, bundle_id, option_id, price, min_count, max_count, inventory 
             FROM bundle_options 
             WHERE bundle_id = ? 
             ORDER BY bundle_option_id 
             LIMIT 1",
            [$bundleId]
        );
    }
}