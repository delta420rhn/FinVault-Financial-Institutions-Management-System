-- ============================================================
-- FinVault – Financial Institutions' Management System
-- Buy & Sell Stock – Complete SQL Backend
-- Group 5 | CSE303L | Instructor: Noureen Islam
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- DROP TABLES (safe re-run order — children before parents)
-- ────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS fraud_investigation_reports;
DROP TABLE IF EXISTS clarification_requests;
DROP TABLE IF EXISTS compliance_reviews;
DROP TABLE IF EXISTS compliance_actions;
DROP TABLE IF EXISTS audit_findings;
DROP TABLE IF EXISTS correction_recommendations;
DROP TABLE IF EXISTS policy_updates;
DROP TABLE IF EXISTS trade_records;
DROP TABLE IF EXISTS purchase_requests;
DROP TABLE IF EXISTS stock_update_requests;
DROP TABLE IF EXISTS stocks;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ============================================================
-- TABLE 1: roles
-- ============================================================
CREATE TABLE roles (
    role_id     INT           PRIMARY KEY AUTO_INCREMENT,
    role_name   VARCHAR(50)   NOT NULL UNIQUE,  -- 'Stock Manager', 'Trade Manager', etc.
    description VARCHAR(255)
);

-- ============================================================
-- TABLE 2: users
-- ============================================================
CREATE TABLE users (
    user_id       INT           PRIMARY KEY AUTO_INCREMENT,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,        -- store bcrypt hash, never plain text
    role_id       INT           NOT NULL,
    is_active     BOOLEAN       DEFAULT TRUE,
    created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME,
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- ============================================================
-- TABLE 3: stocks
-- ============================================================
CREATE TABLE stocks (
    stock_id        INT             PRIMARY KEY AUTO_INCREMENT,
    symbol          VARCHAR(10)     NOT NULL UNIQUE,   -- e.g. 'AAPL'
    company_name    VARCHAR(150)    NOT NULL,
    sector          VARCHAR(100),
    shares_available INT            NOT NULL DEFAULT 0,
    price_per_share  DECIMAL(12, 2) NOT NULL,
    market_value     DECIMAL(15, 2) GENERATED ALWAYS AS (shares_available * price_per_share) STORED,
    status          ENUM('Available', 'Low Stock', 'Out of Stock') DEFAULT 'Available',
    last_updated    DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_shares_non_negative CHECK (shares_available >= 0),
    CONSTRAINT chk_price_positive      CHECK (price_per_share  >  0)
);

-- ============================================================
-- TABLE 4: stock_update_requests
--   Stock Manager submits → Auditor approves/rejects
-- ============================================================
CREATE TABLE stock_update_requests (
    request_id      INT     PRIMARY KEY AUTO_INCREMENT,
    stock_id        INT     NOT NULL,
    requested_by    INT     NOT NULL,   -- Stock Manager user_id
    reviewed_by     INT,                -- Auditor user_id (NULL until reviewed)
    old_quantity    INT     NOT NULL,
    new_quantity    INT     NOT NULL,
    reason          TEXT,
    status          ENUM('Pending', 'Approved', 'Rejected', 'Correction Required')
                            DEFAULT 'Pending',
    auditor_note    TEXT,
    requested_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at     DATETIME,
    CONSTRAINT fk_sur_stock      FOREIGN KEY (stock_id)      REFERENCES stocks(stock_id),
    CONSTRAINT fk_sur_requester  FOREIGN KEY (requested_by)  REFERENCES users(user_id),
    CONSTRAINT fk_sur_reviewer   FOREIGN KEY (reviewed_by)   REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 5: purchase_requests
--   Trade Manager → Stock Manager (availability + price confirm)
-- ============================================================
CREATE TABLE purchase_requests (
    request_id       INT            PRIMARY KEY AUTO_INCREMENT,
    stock_id         INT            NOT NULL,
    requested_by     INT            NOT NULL,   -- Trade Manager
    confirmed_by     INT,                        -- Stock Manager
    quantity         INT            NOT NULL,
    target_price     DECIMAL(12,2)  NOT NULL,
    confirmed_price  DECIMAL(12,2),
    justification    TEXT,
    priority         ENUM('Standard','High','Urgent') DEFAULT 'Standard',
    status           ENUM('Pending','Confirmed','Rejected') DEFAULT 'Pending',
    sm_note          TEXT,
    requested_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmed_at     DATETIME,
    CONSTRAINT fk_pr_stock       FOREIGN KEY (stock_id)     REFERENCES stocks(stock_id),
    CONSTRAINT fk_pr_requester   FOREIGN KEY (requested_by) REFERENCES users(user_id),
    CONSTRAINT fk_pr_confirmer   FOREIGN KEY (confirmed_by) REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 6: trade_records
--   Core buy/sell transaction table
--   Trade Manager executes → Auditor reviews
-- ============================================================
CREATE TABLE trade_records (
    trade_id          INT            PRIMARY KEY AUTO_INCREMENT,
    trade_ref         VARCHAR(20)    NOT NULL UNIQUE,  -- e.g. 'TRD-0092'
    stock_id          INT            NOT NULL,
    executed_by       INT            NOT NULL,          -- Trade Manager
    reviewed_by       INT,                              -- Auditor
    trade_type        ENUM('BUY','SELL')  NOT NULL,
    order_mode        ENUM('Market Order','Limit Order','Stop Loss') DEFAULT 'Market Order',
    quantity          INT            NOT NULL,
    price_per_share   DECIMAL(12,2)  NOT NULL,
    total_value       DECIMAL(15,2)  GENERATED ALWAYS AS (quantity * price_per_share) STORED,
    justification     TEXT,
    risk_score        ENUM('Low','Medium','High') DEFAULT 'Low',
    auditor_status    ENUM('Pending','Reviewed','Flagged','Cleared') DEFAULT 'Pending',
    trade_status      ENUM('Pending','Executed','Suspended','Cancelled') DEFAULT 'Pending',
    executed_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at       DATETIME,
    CONSTRAINT fk_tr_stock      FOREIGN KEY (stock_id)    REFERENCES stocks(stock_id),
    CONSTRAINT fk_tr_executor   FOREIGN KEY (executed_by) REFERENCES users(user_id),
    CONSTRAINT fk_tr_reviewer   FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    CONSTRAINT chk_qty_positive CHECK (quantity > 0)
);

-- ============================================================
-- TABLE 7: correction_recommendations
--   Auditor → Stock Manager or Trade Manager
-- ============================================================
CREATE TABLE correction_recommendations (
    correction_id   INT     PRIMARY KEY AUTO_INCREMENT,
    issued_by       INT     NOT NULL,    -- Auditor
    sent_to         INT     NOT NULL,    -- Stock Manager or Trade Manager
    related_ref     VARCHAR(50),         -- Report ID or Trade ID
    details         TEXT    NOT NULL,
    priority        ENUM('Standard','High','Urgent') DEFAULT 'Standard',
    deadline        DATE,
    status          ENUM('Open','Resolved') DEFAULT 'Open',
    resolution_note TEXT,
    issued_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at     DATETIME,
    CONSTRAINT fk_cr_issuer  FOREIGN KEY (issued_by) REFERENCES users(user_id),
    CONSTRAINT fk_cr_sent_to FOREIGN KEY (sent_to)   REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 8: policy_updates
--   Compliance Officer → Auditor (and optionally Fraud Analyst)
-- ============================================================
CREATE TABLE policy_updates (
    policy_id       INT           PRIMARY KEY AUTO_INCREMENT,
    policy_ref      VARCHAR(50)   NOT NULL UNIQUE,   -- e.g. 'SEC-2026-17a'
    title           VARCHAR(255)  NOT NULL,
    details         TEXT          NOT NULL,
    category        ENUM('AML','Insider Trading','Risk Threshold','Reporting','Other')
                                  DEFAULT 'Other',
    issued_by       INT           NOT NULL,           -- Compliance Officer
    recipients      ENUM('Auditor Only','Auditor + Fraud Analyst','All Roles')
                                  DEFAULT 'Auditor Only',
    effective_date  DATE          NOT NULL,
    status          ENUM('Upcoming','Active','Archived') DEFAULT 'Upcoming',
    issued_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pu_issuer FOREIGN KEY (issued_by) REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 9: compliance_reviews
--   Auditor → Compliance Officer
--   (buy transaction compliance review)
-- ============================================================
CREATE TABLE compliance_reviews (
    review_id       INT     PRIMARY KEY AUTO_INCREMENT,
    review_ref      VARCHAR(20) NOT NULL UNIQUE,   -- e.g. 'CRV-0018'
    trade_id        INT     NOT NULL,
    submitted_by    INT     NOT NULL,               -- Auditor
    assigned_to     INT     NOT NULL,               -- Compliance Officer
    risk_level      ENUM('Low','Medium','High') DEFAULT 'Low',
    findings        TEXT,
    status          ENUM('Open','Closed') DEFAULT 'Open',
    submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at       DATETIME,
    CONSTRAINT fk_cvr_trade    FOREIGN KEY (trade_id)     REFERENCES trade_records(trade_id),
    CONSTRAINT fk_cvr_submitter FOREIGN KEY (submitted_by) REFERENCES users(user_id),
    CONSTRAINT fk_cvr_assigned  FOREIGN KEY (assigned_to)  REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 10: compliance_actions
--   Compliance Officer decision on a compliance_review
-- ============================================================
CREATE TABLE compliance_actions (
    action_id       INT     PRIMARY KEY AUTO_INCREMENT,
    review_id       INT     NOT NULL,
    decided_by      INT     NOT NULL,   -- Compliance Officer
    decision        ENUM('Escalate to Fraud Analyst','Issue Policy Reminder',
                         'Clear – No Violation','Suspend Trading Privileges')
                            NOT NULL,
    action_notes    TEXT,
    decided_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ca_review  FOREIGN KEY (review_id)  REFERENCES compliance_reviews(review_id),
    CONSTRAINT fk_ca_decider FOREIGN KEY (decided_by) REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 11: audit_findings
--   Auditor → Compliance Officer (audit status forwarding)
-- ============================================================
CREATE TABLE audit_findings (
    finding_id      INT           PRIMARY KEY AUTO_INCREMENT,
    finding_ref     VARCHAR(20)   NOT NULL UNIQUE,   -- e.g. 'FND-055'
    related_ref     VARCHAR(50),                      -- Trade ID or Report ID
    submitted_by    INT           NOT NULL,            -- Auditor
    sent_to         INT           NOT NULL,            -- Compliance Officer
    finding_text    TEXT          NOT NULL,
    severity        ENUM('Low','Medium','High') DEFAULT 'Medium',
    action_taken    VARCHAR(255),
    status          ENUM('Open','Resolved') DEFAULT 'Open',
    forwarded_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at     DATETIME,
    CONSTRAINT fk_af_submitter FOREIGN KEY (submitted_by) REFERENCES users(user_id),
    CONSTRAINT fk_af_sent_to   FOREIGN KEY (sent_to)      REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 12: clarification_requests
--   Fraud Analyst → Trade Manager
-- ============================================================
CREATE TABLE clarification_requests (
    clarification_id INT    PRIMARY KEY AUTO_INCREMENT,
    clarif_ref       VARCHAR(20) NOT NULL UNIQUE,   -- e.g. 'CLR-008'
    trade_id         INT    NOT NULL,
    requested_by     INT    NOT NULL,   -- Fraud Analyst
    sent_to          INT    NOT NULL,   -- Trade Manager
    pattern_type     ENUM('Volume Spike','Repeated Pattern','Micro-Transactions',
                          'Timing Anomaly','Other') NOT NULL,
    clarification_text TEXT NOT NULL,
    response_text    TEXT,
    deadline         DATE,
    status           ENUM('Awaiting','Received') DEFAULT 'Awaiting',
    requested_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    responded_at     DATETIME,
    CONSTRAINT fk_clr_trade     FOREIGN KEY (trade_id)     REFERENCES trade_records(trade_id),
    CONSTRAINT fk_clr_requester FOREIGN KEY (requested_by) REFERENCES users(user_id),
    CONSTRAINT fk_clr_sent_to   FOREIGN KEY (sent_to)      REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 13: fraud_investigation_reports
--   Fraud Analyst → Auditor
-- ============================================================
CREATE TABLE fraud_investigation_reports (
    report_id           INT     PRIMARY KEY AUTO_INCREMENT,
    report_ref          VARCHAR(20) NOT NULL UNIQUE,   -- e.g. 'FIR-022'
    trade_id            INT     NOT NULL,
    submitted_by        INT     NOT NULL,   -- Fraud Analyst
    sent_to             INT     NOT NULL,   -- Auditor
    verdict             ENUM('CLEAR','SUSPICIOUS','FRAUD CONFIRMED') NOT NULL,
    evidence_summary    TEXT    NOT NULL,
    recommended_action  ENUM('No action required','Continue monitoring',
                             'Suspend trader account','Legal escalation',
                             'Regulatory report') DEFAULT 'No action required',
    auditor_status      ENUM('Pending','Acknowledged') DEFAULT 'Pending',
    submitted_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at     DATETIME,
    CONSTRAINT fk_fir_trade     FOREIGN KEY (trade_id)     REFERENCES trade_records(trade_id),
    CONSTRAINT fk_fir_submitter FOREIGN KEY (submitted_by) REFERENCES users(user_id),
    CONSTRAINT fk_fir_sent_to   FOREIGN KEY (sent_to)      REFERENCES users(user_id)
);


-- ============================================================
-- INDEXES for performance
-- ============================================================
CREATE INDEX idx_trade_ref           ON trade_records(trade_ref);
CREATE INDEX idx_trade_stock         ON trade_records(stock_id);
CREATE INDEX idx_trade_executed_by   ON trade_records(executed_by);
CREATE INDEX idx_trade_status        ON trade_records(trade_status);
CREATE INDEX idx_trade_type          ON trade_records(trade_type);
CREATE INDEX idx_pr_status           ON purchase_requests(status);
CREATE INDEX idx_sur_status          ON stock_update_requests(status);
CREATE INDEX idx_cr_status           ON compliance_reviews(status);
CREATE INDEX idx_clr_status          ON clarification_requests(status);
CREATE INDEX idx_fir_verdict         ON fraud_investigation_reports(verdict);


-- ============================================================
-- SEED DATA
-- ============================================================

-- ── Roles ────────────────────────────────────────────────────
INSERT INTO roles (role_name, description) VALUES
('Stock Manager',      'Manages stock inventory, quantities, and valuation reports'),
('Trade Manager',      'Executes buy/sell trades and submits transaction records'),
('Auditor',            'Reviews reports, approves updates, forwards fraud patterns'),
('Compliance Officer', 'Receives compliance reviews, issues regulatory policies'),
('Fraud Analyst',      'Investigates flagged transactions, submits investigation results');

-- ── Users (passwords shown as plain-text — hash before storing in production) ──
INSERT INTO users (full_name, email, password_hash, role_id) VALUES
('Alice Rahman',    'alice.sm@finvault.com',   '$2b$12$stockmanagerhashedpw001', 1),
('Bob Hossain',     'bob.tm@finvault.com',     '$2b$12$trademanagerhashedpw002', 2),
('Carol Islam',     'carol.au@finvault.com',   '$2b$12$auditorhashedpasswd0003', 3),
('David Chowdhury', 'david.co@finvault.com',   '$2b$12$compliancehashed000004',  4),
('Eva Begum',       'eva.fa@finvault.com',     '$2b$12$fraudanalysthashed0005',  5);

-- ── Stocks ───────────────────────────────────────────────────
INSERT INTO stocks (symbol, company_name, sector, shares_available, price_per_share, status) VALUES
('AAPL',  'Apple Inc.',          'Technology',    4800,  194.62, 'Available'),
('MSFT',  'Microsoft Corp.',     'Technology',    2200,  418.33, 'Available'),
('TSLA',  'Tesla Inc.',          'Automotive',     350,  163.57, 'Low Stock'),
('NVDA',  'NVIDIA Corp.',        'Semiconductors', 900,  875.20, 'Available'),
('AMZN',  'Amazon.com Inc.',     'E-Commerce',    1500,  209.80, 'Available'),
('META',  'Meta Platforms Inc.', 'Social Media',   600,  511.40, 'Available'),
('GOOGL', 'Alphabet Inc.',       'Technology',       0,  171.20, 'Out of Stock'),
('JPMC',  'JPMorgan Chase & Co.','Finance',        3100,  198.45, 'Available');

-- ── Stock Update Requests ─────────────────────────────────────
INSERT INTO stock_update_requests
    (stock_id, requested_by, reviewed_by, old_quantity, new_quantity, reason, status, auditor_note, requested_at, reviewed_at)
VALUES
(1, 1, 3, 4600, 4800, 'New stock delivery received from clearing house', 'Approved',   'Verified with delivery receipt', '2026-03-28 08:00:00', '2026-03-28 09:14:00'),
(4, 1, 3,  860,  900, 'Reconciliation adjustment after quarterly audit',  'Approved',   NULL,                             '2026-03-28 08:10:00', '2026-03-28 09:20:00'),
(3, 1, NULL, 400, 350, 'Write-off from damaged/delisted shares',          'Pending',    NULL,                             '2026-03-27 10:00:00', NULL),
(6, 1, 3,   580,  600, 'Purchased via secondary market',                  'Approved',   NULL,                             '2026-03-27 11:00:00', '2026-03-27 16:00:00'),
(5, 1, NULL, 1480, 1500,'Inventory restock from broker',                  'Pending',    NULL,                             '2026-03-26 09:00:00', NULL);

-- ── Purchase Requests ─────────────────────────────────────────
INSERT INTO purchase_requests
    (stock_id, requested_by, confirmed_by, quantity, target_price, confirmed_price, justification, priority, status, sm_note, requested_at, confirmed_at)
VALUES
(1, 2, 1,  300, 193.00, 194.62, 'Portfolio rebalancing — increase AAPL exposure', 'High',    'Confirmed', 'Confirmed at market price $194.62', '2026-03-28 08:30:00', '2026-03-28 09:00:00'),
(4, 2, NULL, 50, 870.00, NULL,  'Add NVDA ahead of earnings announcement',        'Urgent',  'Pending',   NULL,                               '2026-03-28 08:45:00', NULL),
(7, 2, 1,  100, 170.00, NULL,  'Buy GOOGL for index tracking',                   'Standard','Rejected',  'Out of stock — no shares available', '2026-03-27 14:00:00', '2026-03-27 15:00:00'),
(2, 2, 1,  120, 415.00, 418.33,'MSFT for tech sector allocation',                'Standard','Confirmed', 'Confirmed at $418.33',              '2026-03-26 10:00:00', '2026-03-26 11:00:00');

-- ── Trade Records ─────────────────────────────────────────────
INSERT INTO trade_records
    (trade_ref, stock_id, executed_by, reviewed_by, trade_type, order_mode, quantity, price_per_share, justification, risk_score, auditor_status, trade_status, executed_at, reviewed_at)
VALUES
('TRD-0092', 1, 2, 3,    'BUY',  'Market Order', 200,  194.62, 'Portfolio rebalancing — AAPL',                 'Low',  'Pending',  'Executed',  '2026-03-28 09:32:00', NULL),
('TRD-0091', 3, 2, 3,    'SELL', 'Market Order', 150,  163.57, 'Take profit on TSLA position',                 'Medium','Reviewed', 'Executed',  '2026-03-28 09:18:00', '2026-03-28 10:00:00'),
('TRD-0090', 2, 2, 3,    'BUY',  'Limit Order',   80,  418.33, 'MSFT tech sector allocation',                  'Low',  'Reviewed', 'Executed',  '2026-03-28 08:55:00', '2026-03-28 09:45:00'),
('TRD-0089', 4, 2, NULL,  'BUY',  'Market Order',  60,  875.20, 'NVDA ahead of earnings',                       'Low',  'Pending',  'Pending',   '2026-03-28 08:40:00', NULL),
('TRD-0088', 4, 2, 3,    'BUY',  'Market Order', 600,  875.20, 'Large NVDA bulk buy',                          'High', 'Flagged',  'Suspended', '2026-03-28 08:20:00', '2026-03-28 09:00:00'),
('TRD-0083', 3, 2, 3,    'SELL', 'Market Order', 500,  163.57, 'TSLA sell — reducing exposure',                'High', 'Flagged',  'Suspended', '2026-03-27 17:30:00', '2026-03-27 18:00:00'),
('TRD-0079', 6, 2, 3,    'BUY',  'Market Order',  25,  511.40, 'META position sizing',                         'Medium','Flagged',  'Suspended', '2026-03-27 14:00:00', '2026-03-27 15:00:00'),
('TRD-0071', 1, 2, 3,    'SELL', 'Market Order', 248,  194.38, 'Earnings window trade',                        'Low',  'Cleared',  'Executed',  '2026-03-25 10:00:00', '2026-03-25 11:00:00'),
('TRD-0065', 2, 2, 3,    'BUY',  'Market Order', 180,  418.33, 'Bulk MSFT buy',                                'High', 'Flagged',  'Suspended', '2026-03-22 09:00:00', '2026-03-22 10:00:00');

-- ── Correction Recommendations ────────────────────────────────
INSERT INTO correction_recommendations
    (issued_by, sent_to, related_ref, details, priority, deadline, status, issued_at)
VALUES
(3, 1, 'RPT-2026-010', 'Missing stock movement entries for TSLA in January — resubmit with complete records', 'High',    '2026-03-30', 'Open',     '2026-03-28 09:14:00'),
(3, 1, 'TSLA Qty',     'Quantity mismatch in Q4 reconciliation — verify with warehouse records',              'High',    '2026-03-29', 'Open',     '2026-03-27 11:05:00'),
(3, 2, 'TRD-0079',     'Missing trade justification document — attach signed authorisation',                  'Standard','2026-03-28', 'Resolved', '2026-03-26 10:00:00'),
(3, 1, 'RPT-2026-005', 'Valuation formula error for NVDA — price used is 5% above market rate',              'Urgent',  '2026-03-20', 'Resolved', '2026-03-15 08:00:00');

-- ── Policy Updates ────────────────────────────────────────────
INSERT INTO policy_updates
    (policy_ref, title, details, category, issued_by, recipients, effective_date, status, issued_at)
VALUES
('SEC-2026-17a', 'Dual approval for large buy transactions',
 'All buy transactions exceeding $100,000 must receive dual approval before execution, effective 2026-04-01.',
 'Reporting', 4, 'Auditor + Fraud Analyst', '2026-04-01', 'Upcoming', '2026-03-28 08:00:00'),

('AML-2026-03',  'Same-day multi-trade AML threshold',
 'Flag all same-day multi-trades by a single trader that cumulatively exceed $250,000 for AML review.',
 'AML', 4, 'Auditor Only', '2026-03-20', 'Active', '2026-03-20 08:00:00'),

('INS-2026-01',  'Executive 30-day trade window restriction',
 'All executives are restricted from trading within 30 days of a scheduled earnings announcement.',
 'Insider Trading', 4, 'All Roles', '2026-03-10', 'Active', '2026-03-10 08:00:00'),

('RSK-2025-12',  'Risk score recalibration Q4 2025',
 'Risk scoring algorithm updated — high-risk threshold lowered from $600K to $400K per single transaction.',
 'Risk Threshold', 4, 'Auditor Only', '2026-01-01', 'Archived', '2025-12-01 08:00:00');

-- ── Compliance Reviews ────────────────────────────────────────
INSERT INTO compliance_reviews
    (review_ref, trade_id, submitted_by, assigned_to, risk_level, findings, status, submitted_at)
VALUES
('CRV-0018', 5, 3, 4, 'High',   'Volume anomaly — TRD-0088 NVDA buy is 10x normal daily volume. Possible manipulation.',      'Open',   '2026-03-28 09:30:00'),
('CRV-0017', 6, 3, 4, 'Medium', 'Repeated TSLA sell within 2-hour window. TRD-0083 flagged for layering pattern.',             'Open',   '2026-03-27 18:30:00'),
('CRV-0016', 7, 3, 4, 'Low',    'Minor price deviation on META buy — trade price $511.40 vs market $509.80. Within tolerance.','Closed', '2026-03-20 10:00:00'),
('CRV-0015', 8, 3, 4, 'Low',    'Earnings window trade — AAPL sell TRD-0071. Timing flagged but pre-approved by board.',       'Closed', '2026-03-10 10:00:00');

-- ── Compliance Actions ────────────────────────────────────────
INSERT INTO compliance_actions
    (review_id, decided_by, decision, action_notes, decided_at)
VALUES
(3, 4, 'Issue Policy Reminder',       'Sent INS-2026-01 reminder to Trade Manager.',             '2026-03-20 12:00:00'),
(4, 4, 'Clear – No Violation',        'Pre-board approval documented. No violation found.',       '2026-03-10 14:00:00');

-- ── Audit Findings ────────────────────────────────────────────
INSERT INTO audit_findings
    (finding_ref, related_ref, submitted_by, sent_to, finding_text, severity, action_taken, status, forwarded_at)
VALUES
('FND-055', 'TRD-0088', 3, 4, 'Abnormal volume spike — NVDA bulk buy ×10x normal. Forwarded to fraud analyst.',      'High',   'Escalated to Fraud', 'Open',     '2026-03-28 10:00:00'),
('FND-054', 'TRD-0083', 3, 4, 'Repeated TSLA sell within 2 hours — fraud investigation result: CLEAR.',              'Medium', 'Fraud investigation', 'Resolved', '2026-03-27 19:00:00'),
('FND-052', 'RPT-2026-010', 3, 4, 'Missing stock movement entries in Jan report. Correction issued to Stock Manager.','Medium', 'Correction issued',  'Resolved', '2026-03-15 09:00:00'),
('FND-048', 'TRD-0071', 3, 4, 'Earnings window trade flag — AAPL SELL. Policy reminder sent to Trade Manager.',      'Low',    'Policy reminder sent','Resolved', '2026-03-10 11:00:00');

-- ── Clarification Requests ────────────────────────────────────
INSERT INTO clarification_requests
    (clarif_ref, trade_id, requested_by, sent_to, pattern_type, clarification_text, response_text, deadline, status, requested_at, responded_at)
VALUES
('CLR-008', 5, 5, 2, 'Volume Spike',
 'Trade TRD-0088 shows a NVDA buy of 600 shares at $875.20 (total $525,120). This is 10x your normal daily volume. Please provide business justification and authorisation documentation.',
 NULL, '2026-03-30', 'Awaiting', '2026-03-28 09:00:00', NULL),

('CLR-007', 6, 5, 2, 'Repeated Pattern',
 'Trade TRD-0083: TSLA sell of 500 shares within 2 hours of a prior TSLA sell. Please explain this repeated pattern and confirm it is not layering.',
 'This was a scheduled portfolio rebalancing following a board directive to reduce automotive sector exposure by 15%.', '2026-03-29', 'Received', '2026-03-27 18:00:00', '2026-03-28 08:00:00'),

('CLR-006', 7, 5, 2, 'Micro-Transactions',
 'Trade TRD-0079: META buy of only 25 shares while typical lot size is 100+. Multiple similar micro-transactions detected. Please clarify trading strategy.',
 NULL, '2026-03-31', 'Awaiting', '2026-03-27 15:00:00', NULL),

('CLR-005', 8, 5, 2, 'Timing Anomaly',
 'Trade TRD-0071: AAPL sell executed 3 days before earnings announcement. Please confirm this is pre-approved under the executive window policy.',
 'This trade was pre-approved by the board on 2026-03-20 under waiver WVR-2026-003. Documentation attached.',
 '2026-03-28', 'Received', '2026-03-25 10:30:00', '2026-03-25 14:00:00');

-- ── Fraud Investigation Reports ───────────────────────────────
INSERT INTO fraud_investigation_reports
    (report_ref, trade_id, submitted_by, sent_to, verdict, evidence_summary, recommended_action, auditor_status, submitted_at, acknowledged_at)
VALUES
('FIR-022', 6, 5, 3, 'CLEAR',
 'TRD-0083 TSLA repeated sell pattern investigated. Trade Manager confirmed this was a scheduled portfolio rebalancing per board directive. No manipulation indicators found.',
 'No action required', 'Acknowledged', '2026-03-28 08:00:00', '2026-03-28 10:00:00'),

('FIR-021', 7, 5, 3, 'SUSPICIOUS',
 'TRD-0079 META micro-transaction pattern ongoing. 12 similar micro-buys identified over 3 days. Awaiting Trade Manager clarification CLR-006 before conclusion.',
 'Continue monitoring', 'Pending', '2026-03-27 16:00:00', NULL),

('FIR-019', 8, 5, 3, 'CLEAR',
 'TRD-0071 AAPL earnings window trade confirmed pre-approved under board waiver WVR-2026-003. Documentation verified.',
 'No action required', 'Acknowledged', '2026-03-25 15:00:00', '2026-03-25 16:00:00'),

('FIR-018', 9, 5, 3, 'FRAUD CONFIRMED',
 'TRD-0065 MSFT bulk buy confirmed as wash trading scheme. 3 coordinated accounts identified via IP analysis. Trades executed within 4-second intervals — clearly automated.',
 'Legal escalation', 'Acknowledged', '2026-03-22 14:00:00', '2026-03-22 15:00:00');


-- ============================================================
-- USEFUL VIEWS
-- ============================================================

-- Full trade detail view
CREATE OR REPLACE VIEW vw_trade_details AS
SELECT
    t.trade_ref,
    s.symbol,
    s.company_name,
    t.trade_type,
    t.order_mode,
    t.quantity,
    t.price_per_share,
    t.total_value,
    t.risk_score,
    t.auditor_status,
    t.trade_status,
    t.executed_at,
    u_exec.full_name AS executed_by_name,
    u_rev.full_name  AS reviewed_by_name
FROM trade_records t
JOIN stocks       s      ON t.stock_id    = s.stock_id
JOIN users        u_exec ON t.executed_by = u_exec.user_id
LEFT JOIN users   u_rev  ON t.reviewed_by = u_rev.user_id;

-- Pending stock update approvals for Auditor
CREATE OR REPLACE VIEW vw_pending_stock_updates AS
SELECT
    r.request_id,
    s.symbol,
    s.company_name,
    r.old_quantity,
    r.new_quantity,
    (r.new_quantity - r.old_quantity) AS qty_change,
    r.reason,
    u.full_name AS requested_by_name,
    r.requested_at
FROM stock_update_requests r
JOIN stocks s ON r.stock_id    = s.stock_id
JOIN users  u ON r.requested_by = u.user_id
WHERE r.status = 'Pending';

-- Open clarification requests (Fraud Analyst dashboard)
CREATE OR REPLACE VIEW vw_open_clarifications AS
SELECT
    c.clarif_ref,
    t.trade_ref,
    s.symbol,
    c.pattern_type,
    c.clarification_text,
    c.deadline,
    c.status,
    u_fa.full_name  AS requested_by,
    u_tm.full_name  AS sent_to
FROM clarification_requests c
JOIN trade_records t ON c.trade_id     = t.trade_id
JOIN stocks        s ON t.stock_id     = s.stock_id
JOIN users      u_fa ON c.requested_by = u_fa.user_id
JOIN users      u_tm ON c.sent_to      = u_tm.user_id
WHERE c.status = 'Awaiting';

-- Active compliance reviews for Compliance Officer
CREATE OR REPLACE VIEW vw_open_compliance_reviews AS
SELECT
    cr.review_ref,
    t.trade_ref,
    s.symbol,
    cr.risk_level,
    cr.findings,
    cr.submitted_at,
    u_au.full_name AS submitted_by_name
FROM compliance_reviews cr
JOIN trade_records t  ON cr.trade_id    = t.trade_id
JOIN stocks        s  ON t.stock_id     = s.stock_id
JOIN users      u_au  ON cr.submitted_by = u_au.user_id
WHERE cr.status = 'Open';


-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER $$

-- Approve a stock update request (Auditor action)
CREATE PROCEDURE sp_approve_stock_update(
    IN  p_request_id  INT,
    IN  p_auditor_id  INT,
    IN  p_note        TEXT
)
BEGIN
    DECLARE v_stock_id    INT;
    DECLARE v_new_qty     INT;

    -- Get the stock and new quantity from the request
    SELECT stock_id, new_quantity
    INTO   v_stock_id, v_new_qty
    FROM   stock_update_requests
    WHERE  request_id = p_request_id AND status = 'Pending';

    IF v_stock_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Request not found or not pending';
    END IF;

    -- Update the actual stock quantity
    UPDATE stocks
    SET    shares_available = v_new_qty,
           status = CASE
               WHEN v_new_qty = 0 THEN 'Out of Stock'
               WHEN v_new_qty < 500 THEN 'Low Stock'
               ELSE 'Available'
           END
    WHERE  stock_id = v_stock_id;

    -- Mark request as approved
    UPDATE stock_update_requests
    SET    status      = 'Approved',
           reviewed_by = p_auditor_id,
           auditor_note = p_note,
           reviewed_at  = NOW()
    WHERE  request_id = p_request_id;
END$$

-- Execute a trade (Trade Manager action)
CREATE PROCEDURE sp_execute_trade(
    IN  p_stock_id      INT,
    IN  p_executor_id   INT,
    IN  p_trade_type    ENUM('BUY','SELL'),
    IN  p_order_mode    ENUM('Market Order','Limit Order','Stop Loss'),
    IN  p_quantity      INT,
    IN  p_price         DECIMAL(12,2),
    IN  p_justification TEXT,
    OUT p_trade_ref     VARCHAR(20),
    OUT p_result_msg    VARCHAR(255)
)
BEGIN
    DECLARE v_available INT;
    DECLARE v_new_ref   VARCHAR(20);

    -- Check stock availability for SELL
    SELECT shares_available INTO v_available
    FROM   stocks WHERE stock_id = p_stock_id;

    IF p_trade_type = 'SELL' AND v_available < p_quantity THEN
        SET p_result_msg = 'Insufficient shares available for sell order';
        SET p_trade_ref  = NULL;
    ELSE
        -- Generate trade ref
        SET v_new_ref = CONCAT('TRD-', LPAD((SELECT COUNT(*) + 1 FROM trade_records), 4, '0'));

        -- Insert trade
        INSERT INTO trade_records
            (trade_ref, stock_id, executed_by, trade_type, order_mode,
             quantity, price_per_share, justification, trade_status)
        VALUES
            (v_new_ref, p_stock_id, p_executor_id, p_trade_type, p_order_mode,
             p_quantity, p_price, p_justification, 'Executed');

        -- Update stock quantity
        IF p_trade_type = 'BUY' THEN
            UPDATE stocks SET shares_available = shares_available + p_quantity WHERE stock_id = p_stock_id;
        ELSE
            UPDATE stocks SET shares_available = shares_available - p_quantity WHERE stock_id = p_stock_id;
        END IF;

        -- Update stock status
        UPDATE stocks
        SET status = CASE
            WHEN shares_available = 0   THEN 'Out of Stock'
            WHEN shares_available < 500 THEN 'Low Stock'
            ELSE 'Available'
        END
        WHERE stock_id = p_stock_id;

        SET p_trade_ref  = v_new_ref;
        SET p_result_msg = 'Trade executed successfully';
    END IF;
END$$

DELIMITER ;


-- ============================================================
-- SAMPLE QUERIES (for backend API endpoints)
-- ============================================================

-- [Stock Manager] Get full inventory
-- SELECT * FROM stocks ORDER BY symbol;

-- [Stock Manager] Get pending purchase requests
-- SELECT pr.*, s.symbol, s.company_name, s.shares_available, s.price_per_share
-- FROM purchase_requests pr JOIN stocks s ON pr.stock_id = s.stock_id
-- WHERE pr.status = 'Pending';

-- [Trade Manager] Get available stocks
-- SELECT symbol, company_name, shares_available, price_per_share, status
-- FROM stocks WHERE status != 'Out of Stock';

-- [Trade Manager] Get all own trades
-- SELECT * FROM vw_trade_details WHERE executed_by_name = 'Bob Hossain';

-- [Auditor] Get pending stock updates
-- SELECT * FROM vw_pending_stock_updates;

-- [Auditor] Get all trades by risk score
-- SELECT * FROM vw_trade_details WHERE risk_score = 'High' ORDER BY executed_at DESC;

-- [Compliance Officer] Get open compliance reviews
-- SELECT * FROM vw_open_compliance_reviews;

-- [Fraud Analyst] Get flagged trades
-- SELECT * FROM vw_trade_details WHERE auditor_status = 'Flagged';

-- [Fraud Analyst] Get open clarification requests
-- SELECT * FROM vw_open_clarifications;
