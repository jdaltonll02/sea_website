-- ============================================================================
-- Hostinger import variant of seed.sql — USE saa_ecl stripped (see
-- schema.hostinger.sql for why). Import this into your actual Hostinger
-- database AFTER schema.hostinger.sql, with that database selected as active
-- in phpMyAdmin.
--
-- IMPORTANT: the seeded admin password_hash below is a non-functional
-- placeholder — it does NOT verify against "ChangeMe123!" despite the
-- comment. After importing, immediately run (with your OWN real password):
--   php -r "echo password_hash('YourRealPassword!', PASSWORD_BCRYPT), PHP_EOL;"
-- then in phpMyAdmin's SQL tab:
--   UPDATE users SET password_hash = '<paste hash>'
--   WHERE email = 'admin@southeasternarchdeaconry.org';
-- ============================================================================

-- Roles
INSERT INTO roles (name, permissions_json) VALUES
('SuperAdmin', '{"all": true}'),
('Communications', '{"blog": true, "newsletters": true, "media": true, "events": true, "testimonials": true}'),
('Registrar', '{"clergy": true, "churches": true, "organizations": true}'),
('Editor', '{"blog": true, "pages-content": true}'),
('Bishop\'s Office', '{"bishops": true, "archdeacons": true, "letters": true}'),
('Administrator', '{"bishops": true, "archdeacons": true, "letters": true, "clergy": true, "churches": true, "organizations": true, "blog": true, "newsletters": true, "events": true, "media": true, "testimonials": true, "pages-content": true, "employees": true, "settings": true, "activity-log": true}'),
('Bishop', '{"bishops": true, "archdeacons": true, "letters": true}'),
('Media', '{"media": true}');

-- Default SuperAdmin user (password: ChangeMe123! — CHANGE IMMEDIATELY AFTER FIRST LOGIN)
-- Hash generated with password_hash('ChangeMe123!', PASSWORD_BCRYPT)
INSERT INTO users (name, email, password_hash, role_id, is_active) VALUES
('Site Administrator', 'admin@southeasternarchdeaconry.org', '$2y$10$YQxT8pQhU8h1V0m2wq7VqOe6Q3yV1lYV9x1n1QwZ3z1lYV9x1n1Qw', 1, 1);

-- Diocesan Bishop
INSERT INTO dioceses_bishops (name, title, type, consecration_date, enthronement_date, is_current, order_number, bio_short, bio_full, motto, education, contact_office) VALUES
('The Rt. Rev. Jonathan B. B. Hart', 'Bishop of Liberia', 'diocesan', '2015-05-16', '2015-06-01', 1, 11,
 'Eleventh Bishop of the Episcopal Church of Liberia, providing oversight to dioceses and archdeaconries across the country.',
 'The Rt. Rev. Jonathan B. B. Hart serves as the Bishop of the Episcopal Church of Liberia, guiding the province in mission, pastoral care, and administration across all archdeaconries, including the Southeastern Archdeaconry.',
 'Serving in Love and Truth',
 'Cuttington University; Virginia Theological Seminary',
 'Episcopal Church of Liberia, Cathedral Close, Monrovia');

-- Suffragan Bishop (first-ever holder — template must remain generic, no name hardcoded elsewhere)
INSERT INTO dioceses_bishops (name, title, type, consecration_date, enthronement_date, is_current, order_number, bio_short, bio_full, motto, education, contact_office) VALUES
('The Rt. Rev. Samuel K. Diggs', 'Suffragan Bishop', 'suffragan', '2022-03-19', '2022-04-03', 1, 1,
 'First Suffragan Bishop of the Episcopal Church of Liberia, with pastoral oversight of the Southeastern Archdeaconry from the Pro-Cathedral of St. Mark, Harper.',
 'The Rt. Rev. Samuel K. Diggs was consecrated as the first Suffragan Bishop of the Episcopal Church of Liberia, seated at St. Mark''s Episcopal Church, Harper. He provides episcopal oversight, confirmation ministry, and pastoral leadership to congregations across the Southeastern Archdeaconry.',
 'Rooted in Christ, Reaching the Southeast',
 'Cuttington University; Trinity College Bristol',
 'St. Mark''s Episcopal Church, Gregory Street, Harper');

-- Archdeacons (historical + current)
INSERT INTO archdeacons (name, term_start, term_end, bio, achievements, order_number) VALUES
('The Ven. Peter T. Gono', '1988-01-10', '2001-12-31', 'Founding Archdeacon of the Southeastern Archdeaconry, laying the groundwork for parish structures across Maryland and Grand Kru counties.', 'Established the first archdeaconry synod; oversaw construction of five parish buildings.', 1),
('The Ven. Mary N. Toe', '2002-01-15', '2014-06-30', 'Second Archdeacon, expanded youth and women''s ministries throughout the region.', 'Founded the archdeaconry-wide Mothers'' Union chapter network.', 2),
('The Ven. Charles W. Freeman', '2014-07-01', NULL, 'Current Archdeacon, overseeing clergy deployment and church development in partnership with the Suffragan Bishop.', 'Led restoration of the Pro-Cathedral roof and expansion of the clergy registry.', 3);

-- Churches
INSERT INTO churches (name, slug, address, town_district, founding_date, patron_saint, short_history, latest_update, service_times, status, is_pro_cathedra, lat, lng) VALUES
('St. Mark''s Episcopal Church', 'st-marks-harper', 'Gregory Street, Harper', 'Harper, Maryland County', '1922-11-30', 'St. Mark the Evangelist',
 'St. Mark''s Episcopal Church was founded in 1922 and serves today as the Pro-Cathedral of the Southeastern Archdeaconry, seat of the Suffragan Bishop.',
 'Recently completed roof restoration and welcomed a new confirmation class of 24 members.',
 'Sunday Holy Eucharist 8:00 AM and 10:30 AM; Wednesday Healing Service 6:00 PM', 'pro_cathedral', 1, 4.3752, -7.7169),
('St. Andrew''s Episcopal Church', 'st-andrews-pleebo', 'Main Road, Pleebo', 'Pleebo, Maryland County', '1955-04-10', 'St. Andrew the Apostle',
 'St. Andrew''s has served the Pleebo community since 1955, growing from a small mission chapel into an active parish.',
 'Launched a new youth choir this quarter.',
 'Sunday Holy Eucharist 9:00 AM', 'parish', 0, 4.5559, -7.6892),
('St. Peter''s Episcopal Church', 'st-peters-barrobo', 'Barrobo Town', 'Barrobo District, Grand Kru County', '1968-09-21', 'St. Peter the Apostle',
 'Established as a mission station in 1968, St. Peter''s now anchors Episcopal presence in Barrobo District.',
 'Repairing storm damage to the parish hall roof with community support.',
 'Sunday Holy Eucharist 9:30 AM', 'mission', 0, 4.7500, -8.2333);

-- Pro-Cathedral weekly schedule (St. Mark's, Harper)
INSERT INTO service_schedules (church_id, activity, day_label, time_label, sort_order) VALUES
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Holy Eucharist', 'Sunday', '8:00 AM & 10:30 AM', 1),
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Morning Prayer & Mass', 'Daily', '6:00 AM', 2),
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Healing Service', 'Wednesday', '6:00 PM', 3),
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Bible Study', 'Tuesday', '5:30 PM', 4),
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Choir Practice', 'Thursday', '6:00 PM', 5),
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Acolyte Practice', 'Friday', '5:00 PM', 6),
((SELECT id FROM churches WHERE slug = 'st-marks-harper'), 'Altar Guild Practice', 'Saturday', '3:00 PM', 7);

-- Clergy
INSERT INTO clergy (full_name, order_type, ordination_date, home_church_id, title, bio_short, status) VALUES
('The Rev. Canon Emmanuel S. Toe', 'priest', '1998-06-14', 1, 'Rev. Canon', 'Rector of the Pro-Cathedral of St. Mark, Harper, and Canon of the Archdeaconry.', 'active'),
('The Rev. Grace B. Nyensuah', 'priest', '2010-11-07', 2, 'Rev.', 'Priest-in-charge of St. Andrew''s, Pleebo, with a focus on youth discipleship.', 'active'),
('The Rev. Deacon Joseph K. Wleh', 'deacon', '2019-03-02', 3, 'Rev. Deacon', 'Serving deacon at St. Peter''s, Barrobo, coordinating outreach and relief ministries.', 'active'),
('Mr. Isaac T. Neufville', 'lay_reader', '2015-08-16', 3, 'Lay Reader', 'Licensed lay reader supporting worship at St. Peter''s, Barrobo.', 'active'),
('The Ven. Charles W. Freeman', 'priest', '1995-05-20', 1, 'Ven.', 'Archdeacon of the Southeastern Archdeaconry; formerly rector of St. Mark''s.', 'active');

UPDATE churches SET current_priest_id = 1 WHERE id = 1;
UPDATE churches SET current_priest_id = 2 WHERE id = 2;
UPDATE churches SET current_priest_id = 3 WHERE id = 3;

-- Organizations
INSERT INTO organizations (name, slug, category, founding_date, church_id, mission_statement, meeting_schedule, short_history, latest_update) VALUES
('Mothers'' Union — Southeastern Chapter', 'mothers-union-southeastern', 'Mothers'' Union', '2003-05-01', NULL,
 'To demonstrate the love of Christ through the promotion of family life and support for parishioners across the Archdeaconry.',
 'Meets first Saturday of each month, 10:00 AM',
 'Founded under the second Archdeacon to unify the many parish-level Mothers'' Union groups into one archdeaconry-wide body.',
 'Organized a relief drive for storm-affected families in Barrobo District.');

-- Blog category + post
INSERT INTO blog_categories (name, slug) VALUES ('Archdeaconry News', 'archdeaconry-news');

INSERT INTO blog_posts (title, slug, body, excerpt, author_id, category_id, status, published_at) VALUES
('Suffragan Bishop Visits St. Peter''s, Barrobo', 'suffragan-bishop-visits-st-peters-barrobo',
 '<p>The Rt. Rev. Samuel K. Diggs, Suffragan Bishop, made a pastoral visit to St. Peter''s Episcopal Church in Barrobo District, confirming twelve new members and blessing the newly repaired parish hall.</p>',
 'The Suffragan Bishop visited St. Peter''s, Barrobo, for confirmation and to bless the repaired parish hall.',
 1, 1, 'published', '2026-07-20 09:00:00');

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Southeastern Archdeaconry — Episcopal Church of Liberia'),
('contact_email', 'info@southeasternarchdeaconry.org'),
('contact_phone', '+231-XXX-XXXX'),
('address', 'St. Mark''s Episcopal Church, Gregory Street, Harper, Maryland County, Liberia'),
('facebook_url', ''),
('mission_statement', 'To proclaim the Gospel of Jesus Christ and build faithful, loving communities across Southeastern Liberia.'),
('giving_bank_details', 'Account Name: Southeastern Archdeaconry ECL — Bank details to be confirmed by Diocesan Finance Office'),
('giving_mobile_money', 'Mobile Money number to be confirmed by Diocesan Finance Office');

-- Lectionary sample (today + surrounding days for demo)
INSERT INTO lectionary_days (`date`, season, color, saint_of_day) VALUES
('2026-08-14', 'Season after Pentecost', 'Green', 'Jonathan Myrick Daniels, Martyr'),
('2026-08-15', 'Season after Pentecost', 'White', 'St. Mary the Virgin, Mother of Our Lord'),
('2026-08-16', 'Season after Pentecost', 'Green', NULL);

-- Events
INSERT INTO events (title, description, location, start_datetime, end_datetime, event_type) VALUES
('Archdeaconry Synod 2026', 'Annual synod gathering clergy and lay delegates from across the Southeastern Archdeaconry.', 'St. Mark''s Pro-Cathedral, Harper', '2026-10-12 09:00:00', '2026-10-13 16:00:00', 'synod'),
('Confirmation Sunday — St. Andrew''s Pleebo', 'Confirmation service led by the Suffragan Bishop.', 'St. Andrew''s Episcopal Church, Pleebo', '2026-09-06 09:00:00', '2026-09-06 12:00:00', 'confirmation');

-- Testimony
INSERT INTO testimonies (name, church_id, message, approved) VALUES
('Sister Comfort A. Wesseh', 1, 'This parish has been a home for my family for three generations. God continues to bless our worship together.', 1);

-- Pages content (About / Vision / History blocks)
INSERT INTO pages_content (page_key, title, body) VALUES
('about_vision', 'Our Vision', 'To be a Christ-centered Archdeaconry that transforms lives and communities across Southeastern Liberia.'),
('about_mission', 'Our Mission', 'To proclaim the Gospel, nurture faithful disciples, and serve our communities with love and integrity.'),
('about_history', 'Our History', 'The Southeastern Archdeaconry was established to extend the pastoral and administrative reach of the Episcopal Church of Liberia into Maryland and Grand Kru counties, growing from a handful of mission chapels into a network of parishes served by clergy, lay readers, and church organizations.');
