DROP PROCEDURE IF EXISTS sp_get_analytics_executive_report;

CREATE PROCEDURE sp_get_analytics_executive_report(IN p_timeframe VARCHAR(20))
BEGIN
    DECLARE v_date_from DATETIME DEFAULT NULL;
    DECLARE v_has_timeframe_orders INT DEFAULT 0;

    -- Determine timeframe window
    IF p_timeframe = '7d' OR p_timeframe = '7' THEN
        SET v_date_from = DATE_SUB(NOW(), INTERVAL 7 DAY);
    ELSEIF p_timeframe = '30d' OR p_timeframe = '30' OR p_timeframe = 'month' THEN
        SET v_date_from = DATE_SUB(NOW(), INTERVAL 30 DAY);
    ELSEIF p_timeframe = '90d' OR p_timeframe = '90' OR p_timeframe = 'quarter' THEN
        SET v_date_from = DATE_SUB(NOW(), INTERVAL 90 DAY);
    ELSEIF p_timeframe = 'year' OR p_timeframe = '365' OR p_timeframe = '365d' THEN
        SET v_date_from = DATE_SUB(NOW(), INTERVAL 365 DAY);
    ELSE
        SET v_date_from = NULL;
    END IF;

    -- Check if any orders exist in timeframe
    IF v_date_from IS NOT NULL THEN
        SELECT COUNT(*) INTO v_has_timeframe_orders
        FROM customer_orders
        WHERE (order_date >= v_date_from OR (order_date IS NULL AND created_at >= v_date_from));
    ELSE
        SET v_has_timeframe_orders = 1;
    END IF;

    SELECT
        -- Gross Revenue: Active orders + refunded transactions
        COALESCE(
            SUM(CASE 
                WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                THEN 
                    CASE 
                        WHEN co.ref_order_status_id != 6 THEN co.total_amount
                        WHEN co.refund_amount > 0 THEN co.refund_amount
                        ELSE 0
                    END
                ELSE 0 
            END), 0
        ) AS gross_sales,

        -- Customer Refunds
        COALESCE(
            SUM(CASE 
                WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                     AND (co.refund_status != 'None' OR co.refund_amount > 0)
                THEN co.refund_amount 
                ELSE 0 
            END), 0
        ) AS total_refunds,

        -- Net Realized Store Revenue
        COALESCE(
            SUM(CASE 
                WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                     AND co.ref_order_status_id != 6 
                THEN (co.total_amount - COALESCE(co.refund_amount, 0))
                ELSE 0 
            END), 0
        ) AS net_store_sales,

        -- 12% VAT portion of net realized sales
        COALESCE(
            ROUND(
                SUM(CASE 
                    WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                         AND co.ref_order_status_id != 6 
                    THEN (co.total_amount - COALESCE(co.refund_amount, 0))
                    ELSE 0 
                END) * 0.12, 2
            ), 0
        ) AS vat_collected,

        -- Courier Logistics
        COALESCE(
            ROUND(
                COUNT(CASE 
                    WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                         AND co.ref_order_status_id != 6
                    THEN 1 
                    ELSE NULL 
                END) * 150.00, 2
            ), 0
        ) AS est_logistics_expense,

        -- Order counts
        COUNT(CASE 
            WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                 AND co.ref_order_status_id != 6
            THEN 1 
            ELSE NULL 
        END) AS total_orders,

        -- Average Order Value
        COALESCE(
            ROUND(
                SUM(CASE 
                    WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                         AND co.ref_order_status_id != 6 
                    THEN co.total_amount 
                    ELSE 0 
                END) /
                NULLIF(COUNT(CASE 
                    WHEN (v_date_from IS NULL OR v_has_timeframe_orders = 0 OR COALESCE(co.order_date, co.created_at) >= v_date_from)
                         AND co.ref_order_status_id != 6
                    THEN 1 
                    ELSE NULL 
                END), 0), 2
            ), 0
        ) AS average_order_value,

        -- Inventory Capital KPIs
        (SELECT COALESCE(SUM(stock * price), 0) FROM products WHERE stock > 0) AS tied_up_capital,
        (SELECT COALESCE(SUM(stock), 0) FROM products) AS total_inventory_units,
        (SELECT COUNT(*) FROM products WHERE stock <= 10 AND stock > 0) AS low_stock_count,
        (SELECT COUNT(*) FROM products WHERE stock = 0) AS out_of_stock_count,
        (SELECT COUNT(*) FROM products WHERE status = 'active') AS total_active_skus,

        -- Customer KPIs
        (SELECT COUNT(DISTINCT COALESCE(user_id, customer_profile_id)) FROM customer_orders WHERE ref_order_status_id != 6) AS active_customers_count,
        (SELECT COUNT(*) FROM customer_cart) AS total_cart_items,
        (SELECT COUNT(*) FROM customer_favourites) AS total_favourites_count
    FROM customer_orders co;
END;

