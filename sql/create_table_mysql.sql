CREATE TABLE dtb_paypal_regular_order (
    order_id int NOT NULL,
    txn_id varchar(32),
    scheduled_date datetime NOT NULL,
    settlement_date datetime,
    settlement_status smallint NOT NULL DEFAULT 1,
    create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_date timestamp NOT NULL,
    PRIMARY KEY (order_id),
    UNIQUE (order_id, txn_id)
);
CREATE TABLE mtb_paypal_payment_status (
    id smallint,
    name text,
    rank smallint NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);
