SELECT po.id, po.order_number, po.order_date, po.expected_delivery, po.status, po.total_amount,
       s.name as supplier_name, s.contact_person as supplier_contact,
       COUNT(poi.id) as total_items
FROM purchase_orders po
LEFT JOIN suppliers s ON po.supplier_id = s.id
LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
GROUP BY po.id
ORDER BY po.order_date DESC
LIMIT 10;
