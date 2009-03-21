DROP TABLE IF EXISTS message_queue;
CREATE TABLE message_queue (
    uuid VARCHAR(40) NOT NULL,
    owner VARCHAR(255) DEFAULT NULL,
    batch_uuid VARCHAR(40) DEFAULT NULL,
    batch_seq INTEGER DEFAULT 0,
    created DATETIME,
    modified DATETIME,
    scheduled_for DATETIME,
    reserved_at DATETIME,
    reserved_until DATETIME,
    finished_at DATETIME,
    priority INTEGER DEFAULT 0,
    topic VARCHAR(255),
    object VARCHAR(255),
    method VARCHAR(255),
    context TEXT DEFAULT NULL,
    body TEXT DEFAULT NULL,
    signature CHAR(32) DEFAULT NULL,
    PRIMARY KEY (uuid),
    KEY created (created),
    KEY priority (priority),
    KEY batch_seq (batch_seq),
    KEY signature (signature),
    KEY reserved_at (reserved_at),
    KEY finished_at (finished_at),
    KEY scheduled_for (scheduled_for),
    KEY batch_uuid (batch_uuid)
) CHARACTER SET utf8 COLLATE utf8_general_ci;
