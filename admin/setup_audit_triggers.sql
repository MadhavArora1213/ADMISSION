-- =============================================
-- AUDIT LOGGING TRIGGERS FOR ADMISSION DATABASE
-- =============================================

DELIMITER $$

DROP TRIGGER IF EXISTS trg_users_after_insert$$
CREATE TRIGGER trg_users_after_insert AFTER INSERT ON users
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NEW.id, 'create', 'users', NEW.id, NULL,
        JSON_OBJECT('full_name', NEW.full_name, 'email', NEW.email, 'status', NEW.status, 'role_id', NEW.role_id),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_users_after_update$$
CREATE TRIGGER trg_users_after_update AFTER UPDATE ON users
FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
        VALUES (UUID(), NEW.id, 'status_change', 'users', NEW.id,
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status),
            NULL, NOW());
    ELSEIF OLD.full_name != NEW.full_name OR OLD.email != NEW.email OR OLD.role_id != NEW.role_id THEN
        INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
        VALUES (UUID(), NEW.id, 'update', 'users', NEW.id,
            JSON_OBJECT('full_name', OLD.full_name, 'email', OLD.email, 'role_id', OLD.role_id),
            JSON_OBJECT('full_name', NEW.full_name, 'email', NEW.email, 'role_id', NEW.role_id),
            NULL, NOW());
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_users_after_delete$$
CREATE TRIGGER trg_users_after_delete AFTER DELETE ON users
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'users', OLD.id,
        JSON_OBJECT('full_name', OLD.full_name, 'email', OLD.email, 'status', OLD.status),
        NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_colleges_after_insert$$
CREATE TRIGGER trg_colleges_after_insert AFTER INSERT ON colleges
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'colleges', NEW.id, NULL,
        JSON_OBJECT('name', NEW.name, 'slug', NEW.slug, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_colleges_after_update$$
CREATE TRIGGER trg_colleges_after_update AFTER UPDATE ON colleges
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'colleges', NEW.id,
        JSON_OBJECT('name', OLD.name, 'status', OLD.status),
        JSON_OBJECT('name', NEW.name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_colleges_after_delete$$
CREATE TRIGGER trg_colleges_after_delete AFTER DELETE ON colleges
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'colleges', OLD.id,
        JSON_OBJECT('name', OLD.name, 'slug', OLD.slug),
        NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_universities_after_insert$$
CREATE TRIGGER trg_universities_after_insert AFTER INSERT ON universities
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'universities', NEW.id, NULL,
        JSON_OBJECT('name', NEW.name, 'slug', NEW.slug, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_universities_after_update$$
CREATE TRIGGER trg_universities_after_update AFTER UPDATE ON universities
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'universities', NEW.id,
        JSON_OBJECT('name', OLD.name, 'status', OLD.status),
        JSON_OBJECT('name', NEW.name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_universities_after_delete$$
CREATE TRIGGER trg_universities_after_delete AFTER DELETE ON universities
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'universities', OLD.id,
        JSON_OBJECT('name', OLD.name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_leads_after_insert$$
CREATE TRIGGER trg_leads_after_insert AFTER INSERT ON leads
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'leads', NEW.id, NULL,
        JSON_OBJECT('name', NEW.name, 'email', NEW.email, 'lead_status', NEW.lead_status, 'source_page', NEW.source_page),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_leads_after_update$$
CREATE TRIGGER trg_leads_after_update AFTER UPDATE ON leads
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'leads', NEW.id,
        JSON_OBJECT('lead_status', OLD.lead_status, 'assigned_to', OLD.assigned_to),
        JSON_OBJECT('lead_status', NEW.lead_status, 'assigned_to', NEW.assigned_to),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_leads_after_delete$$
CREATE TRIGGER trg_leads_after_delete AFTER DELETE ON leads
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'leads', OLD.id,
        JSON_OBJECT('name', OLD.name, 'email', OLD.email), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_articles_after_insert$$
CREATE TRIGGER trg_articles_after_insert AFTER INSERT ON articles
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'articles', NEW.id, NULL,
        JSON_OBJECT('article_title', NEW.article_title, 'status', NEW.status, 'author_id', NEW.author_id),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_articles_after_update$$
CREATE TRIGGER trg_articles_after_update AFTER UPDATE ON articles
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'articles', NEW.id,
        JSON_OBJECT('article_title', OLD.article_title, 'status', OLD.status),
        JSON_OBJECT('article_title', NEW.article_title, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_articles_after_delete$$
CREATE TRIGGER trg_articles_after_delete AFTER DELETE ON articles
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'articles', OLD.id,
        JSON_OBJECT('article_title', OLD.article_title, 'status', OLD.status), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_exams_after_insert$$
CREATE TRIGGER trg_exams_after_insert AFTER INSERT ON exams
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'exams', NEW.id, NULL,
        JSON_OBJECT('exam_name', NEW.exam_name, 'exam_slug', NEW.exam_slug),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_exams_after_update$$
CREATE TRIGGER trg_exams_after_update AFTER UPDATE ON exams
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'exams', NEW.id,
        JSON_OBJECT('exam_name', OLD.exam_name, 'status', OLD.status),
        JSON_OBJECT('exam_name', NEW.exam_name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_exams_after_delete$$
CREATE TRIGGER trg_exams_after_delete AFTER DELETE ON exams
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'exams', OLD.id,
        JSON_OBJECT('exam_name', OLD.exam_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_courses_after_insert$$
CREATE TRIGGER trg_courses_after_insert AFTER INSERT ON courses
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'courses', NEW.id, NULL,
        JSON_OBJECT('course_name', NEW.course_name, 'course_slug', NEW.course_slug),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_courses_after_update$$
CREATE TRIGGER trg_courses_after_update AFTER UPDATE ON courses
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'courses', NEW.id,
        JSON_OBJECT('course_name', OLD.course_name, 'status', OLD.status),
        JSON_OBJECT('course_name', NEW.course_name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_courses_after_delete$$
CREATE TRIGGER trg_courses_after_delete AFTER DELETE ON courses
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'courses', OLD.id,
        JSON_OBJECT('course_name', OLD.course_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_scholarships_after_insert$$
CREATE TRIGGER trg_scholarships_after_insert AFTER INSERT ON scholarships
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'scholarships', NEW.id, NULL,
        JSON_OBJECT('scholarship_name', NEW.scholarship_name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_scholarships_after_update$$
CREATE TRIGGER trg_scholarships_after_update AFTER UPDATE ON scholarships
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'scholarships', NEW.id,
        JSON_OBJECT('scholarship_name', OLD.scholarship_name, 'status', OLD.status),
        JSON_OBJECT('scholarship_name', NEW.scholarship_name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_scholarships_after_delete$$
CREATE TRIGGER trg_scholarships_after_delete AFTER DELETE ON scholarships
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'scholarships', OLD.id,
        JSON_OBJECT('scholarship_name', OLD.scholarship_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_applications_after_insert$$
CREATE TRIGGER trg_applications_after_insert AFTER INSERT ON applications
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'applications', NEW.id, NULL,
        JSON_OBJECT('user_id', NEW.user_id, 'college_id', NEW.college_id, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_applications_after_update$$
CREATE TRIGGER trg_applications_after_update AFTER UPDATE ON applications
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'applications', NEW.id,
        JSON_OBJECT('status', OLD.status, 'payment_status', OLD.payment_status),
        JSON_OBJECT('status', NEW.status, 'payment_status', NEW.payment_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_roles_after_insert$$
CREATE TRIGGER trg_roles_after_insert AFTER INSERT ON roles
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'roles', NEW.id, NULL,
        JSON_OBJECT('role_name', NEW.role_name, 'permissions', NEW.permissions),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_roles_after_update$$
CREATE TRIGGER trg_roles_after_update AFTER UPDATE ON roles
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'permission_change', 'roles', NEW.id,
        JSON_OBJECT('role_name', OLD.role_name, 'permissions', OLD.permissions),
        JSON_OBJECT('role_name', NEW.role_name, 'permissions', NEW.permissions),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_roles_after_delete$$
CREATE TRIGGER trg_roles_after_delete AFTER DELETE ON roles
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'roles', OLD.id,
        JSON_OBJECT('role_name', OLD.role_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_reviews_after_insert$$
CREATE TRIGGER trg_reviews_after_insert AFTER INSERT ON reviews
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'reviews', NEW.id, NULL,
        JSON_OBJECT('user_id', NEW.user_id, 'college_id', NEW.college_id, 'overall_rating', NEW.overall_rating, 'moderation_status', NEW.moderation_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_reviews_after_update$$
CREATE TRIGGER trg_reviews_after_update AFTER UPDATE ON reviews
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'reviews', NEW.id,
        JSON_OBJECT('moderation_status', OLD.moderation_status, 'overall_rating', OLD.overall_rating),
        JSON_OBJECT('moderation_status', NEW.moderation_status, 'overall_rating', NEW.overall_rating),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_subscriptions_after_insert$$
CREATE TRIGGER trg_subscriptions_after_insert AFTER INSERT ON subscriptions
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'subscriptions', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'amount', NEW.amount, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_subscriptions_after_update$$
CREATE TRIGGER trg_subscriptions_after_update AFTER UPDATE ON subscriptions
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'status_change', 'subscriptions', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_invoices_after_insert$$
CREATE TRIGGER trg_invoices_after_insert AFTER INSERT ON invoices
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'invoices', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'total_amount', NEW.total_amount, 'payment_status', NEW.payment_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_invoices_after_update$$
CREATE TRIGGER trg_invoices_after_update AFTER UPDATE ON invoices
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'invoices', NEW.id,
        JSON_OBJECT('payment_status', OLD.payment_status, 'total_amount', OLD.total_amount),
        JSON_OBJECT('payment_status', NEW.payment_status, 'total_amount', NEW.total_amount),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_college_accounts_after_insert$$
CREATE TRIGGER trg_college_accounts_after_insert AFTER INSERT ON college_accounts
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'college_accounts', NEW.id, NULL,
        JSON_OBJECT('institute_name', NEW.institute_name, 'email', NEW.email, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_college_accounts_after_update$$
CREATE TRIGGER trg_college_accounts_after_update AFTER UPDATE ON college_accounts
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'college_accounts', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_moderation_queue_after_insert$$
CREATE TRIGGER trg_moderation_queue_after_insert AFTER INSERT ON moderation_queue
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'moderation_queue', NEW.id, NULL,
        JSON_OBJECT('entity_type', NEW.entity_type, 'entity_id', NEW.entity_id, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_moderation_queue_after_update$$
CREATE TRIGGER trg_moderation_queue_after_update AFTER UPDATE ON moderation_queue
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'status_change', 'moderation_queue', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_partners_after_insert$$
CREATE TRIGGER trg_partners_after_insert AFTER INSERT ON partners
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'partners', NEW.id, NULL,
        JSON_OBJECT('contact_person', NEW.contact_person, 'partner_college_id', NEW.partner_college_id, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_partners_after_update$$
CREATE TRIGGER trg_partners_after_update AFTER UPDATE ON partners
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'partners', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_partners_after_delete$$
CREATE TRIGGER trg_partners_after_delete AFTER DELETE ON partners
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'partners', OLD.id,
        JSON_OBJECT('contact_person', OLD.contact_person), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_payments_after_insert$$
CREATE TRIGGER trg_payments_after_insert AFTER INSERT ON payments
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'payments', NEW.id, NULL,
        JSON_OBJECT('application_id', NEW.application_id, 'amount', NEW.amount, 'payment_status', NEW.payment_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_payments_after_update$$
CREATE TRIGGER trg_payments_after_update AFTER UPDATE ON payments
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'payments', NEW.id,
        JSON_OBJECT('payment_status', OLD.payment_status),
        JSON_OBJECT('payment_status', NEW.payment_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_lead_credits_after_insert$$
CREATE TRIGGER trg_lead_credits_after_insert AFTER INSERT ON lead_credits
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'lead_credits', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'leads_purchased', NEW.leads_purchased, 'lead_cost', NEW.lead_cost),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_commissions_after_insert$$
CREATE TRIGGER trg_commissions_after_insert AFTER INSERT ON commissions
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'commissions', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'commission_earned', NEW.commission_earned, 'commission_status', NEW.commission_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_commissions_after_update$$
CREATE TRIGGER trg_commissions_after_update AFTER UPDATE ON commissions
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'commissions', NEW.id,
        JSON_OBJECT('commission_status', OLD.commission_status),
        JSON_OBJECT('commission_status', NEW.commission_status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_foreign_universities_after_insert$$
CREATE TRIGGER trg_foreign_universities_after_insert AFTER INSERT ON foreign_universities
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'foreign_universities', NEW.id, NULL,
        JSON_OBJECT('university_name', NEW.university_name, 'country', NEW.country),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_foreign_universities_after_delete$$
CREATE TRIGGER trg_foreign_universities_after_delete AFTER DELETE ON foreign_universities
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'foreign_universities', OLD.id,
        JSON_OBJECT('university_name', OLD.university_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_consultants_after_insert$$
CREATE TRIGGER trg_consultants_after_insert AFTER INSERT ON consultants
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'consultants', NEW.id, NULL,
        JSON_OBJECT('consultant_name', NEW.consultant_name, 'contact_email', NEW.contact_email),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_consultants_after_delete$$
CREATE TRIGGER trg_consultants_after_delete AFTER DELETE ON consultants
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'consultants', OLD.id,
        JSON_OBJECT('consultant_name', OLD.consultant_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_questions_after_delete$$
CREATE TRIGGER trg_questions_after_delete AFTER DELETE ON questions
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'questions', OLD.id,
        JSON_OBJECT('question_text', LEFT(OLD.question_text, 200), 'asked_by', OLD.asked_by), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_answers_after_delete$$
CREATE TRIGGER trg_answers_after_delete AFTER DELETE ON answers
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'answers', OLD.id,
        JSON_OBJECT('question_id', OLD.question_id, 'answered_by', OLD.answered_by), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_article_categories_after_insert$$
CREATE TRIGGER trg_article_categories_after_insert AFTER INSERT ON article_categories
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'article_categories', NEW.id, NULL,
        JSON_OBJECT('category_name', NEW.category_name, 'category_slug', NEW.category_slug),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_article_categories_after_delete$$
CREATE TRIGGER trg_article_categories_after_delete AFTER DELETE ON article_categories
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'article_categories', OLD.id,
        JSON_OBJECT('category_name', OLD.category_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_notification_templates_after_insert$$
CREATE TRIGGER trg_notification_templates_after_insert AFTER INSERT ON notification_templates
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'notification_templates', NEW.id, NULL,
        JSON_OBJECT('template_name', NEW.template_name, 'channel', NEW.channel),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_notification_templates_after_delete$$
CREATE TRIGGER trg_notification_templates_after_delete AFTER DELETE ON notification_templates
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'notification_templates', OLD.id,
        JSON_OBJECT('template_name', OLD.template_name), NULL, NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_notification_campaigns_after_insert$$
CREATE TRIGGER trg_notification_campaigns_after_insert AFTER INSERT ON notification_campaigns
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'notification_campaigns', NEW.id, NULL,
        JSON_OBJECT('campaign_name', NEW.campaign_name, 'status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_notification_campaigns_after_update$$
CREATE TRIGGER trg_notification_campaigns_after_update AFTER UPDATE ON notification_campaigns
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'status_change', 'notification_campaigns', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END$$

DROP TRIGGER IF EXISTS trg_notification_campaigns_after_delete$$
CREATE TRIGGER trg_notification_campaigns_after_delete AFTER DELETE ON notification_campaigns
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'notification_campaigns', OLD.id,
        JSON_OBJECT('campaign_name', OLD.campaign_name), NULL, NULL, NOW());
END$$

DELIMITER ;
