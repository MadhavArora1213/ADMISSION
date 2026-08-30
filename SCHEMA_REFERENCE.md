# AdmissionSeason — Database Schema (Universities & Colleges)

> Complete consolidated schema for scraping data into the AdmissionSeason platform.
> Database: `admission` (MySQL/MariaDB)

---

## Reference Tables

### `states`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `name` | VARCHAR(100) NOT NULL | e.g. "Maharashtra", "Delhi" |

### `cities`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `state_id` | INT NOT NULL | FK → states.id |
| `name` | VARCHAR(100) NOT NULL | e.g. "Mumbai", "Pune" |

### `courses` (reference)
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `name` | VARCHAR(255) NOT NULL | e.g. "B.Tech Computer Science" |
| `slug` | VARCHAR(255) UNIQUE | e.g. "btech-cse" |
| `level` | ENUM('UG','PG','Diploma','PhD','Certificate') | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

### `exams` (reference)
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `name` | VARCHAR(100) NOT NULL | e.g. "JEE Main", "NEET", "CAT" |
| `slug` | VARCHAR(100) UNIQUE | |
| `level` | ENUM('National','State','University') | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

---

## Universities

### `universities` (core)
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `name` | VARCHAR(300) NOT NULL | Full university name |
| `slug` | VARCHAR(300) UNIQUE NOT NULL | URL-friendly slug |
| `university_type` | ENUM('govt','private','deemed','autonomous') | |
| `ownership` | ENUM('central','state','private_trust','minority') | |
| `status` | ENUM('active','pending','archived','rejected') | DEFAULT 'pending' |
| `is_featured` | BOOLEAN | DEFAULT FALSE |
| `is_verified` | BOOLEAN | DEFAULT FALSE |
| `featured_order` | INT | DEFAULT 0 |
| `ranking_nirf` | INT | NIRF rank |
| `ranking_qs` | INT | QS rank |
| `ranking_times` | INT | Times rank |
| `nirf_rank` | INT | Alternative NIRF column |
| `city_id` | INT | FK → cities.id |
| `state_id` | INT | FK → states.id |
| `established_year` | YEAR / INT | Year established |
| `founded_year` | YEAR | Alternative founded year |
| `type_label` | VARCHAR(100) | Custom type label |
| `campus_type` | ENUM('urban','semi-urban','rural') | |
| `data_quality_score` | TINYINT | DEFAULT 0 |
| `logo_url` | VARCHAR(255) | Path like `uploads/universities/xxx_logo_xxx.png` |
| `cover_image_url` | VARCHAR(255) | Path like `uploads/universities/xxx_cover_xxx.png` |
| `autonomous` | BOOLEAN | DEFAULT FALSE |
| `naac_grade` | ENUM('A++','A+','A','B++','B+','B','C','None') | |
| `ugc_approved` | BOOLEAN | DEFAULT FALSE |
| `aicte_approved` | BOOLEAN | DEFAULT FALSE |
| `nba_approved` | BOOLEAN | DEFAULT FALSE |
| `total_students` | INT | |
| `total_faculty` | INT | |
| `campus_area_acres` | FLOAT | |
| `overall_rating_avg` | FLOAT | DEFAULT 0 |
| `total_reviews` | INT | DEFAULT 0 |
| `rating_distribution` | JSON | Breakdown by category |
| `verified_reviews_count` | INT | DEFAULT 0 |
| `publish_status` | ENUM('draft','published','archived') | DEFAULT 'draft' |
| `verification_status` | ENUM('unverified','pending','verified','disputed') | |
| `verified_by` | CHAR(36) | FK → users.id |
| `verified_at` | TIMESTAMP | |
| `rejection_reason` | TEXT | |
| `duplicate_of` | CHAR(36) | FK → universities.id |
| `import_batch_id` | CHAR(36) | |
| `last_data_audit_at` | TIMESTAMP | |
| `archived_at` | TIMESTAMP | |
| `published_at` | TIMESTAMP | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | auto-update |
| `about_text` | LONGTEXT | About the university |
| `highlights_json` | JSON / LONGTEXT | Key highlights |
| `accreditations` | LONGTEXT | |
| `rankings_json` | JSON / LONGTEXT | |
| `awards_json` | JSON / LONGTEXT | |
| `admission_process` | TEXT | |
| `accepted_exams` | JSON / LONGTEXT | e.g. ["JEE Main","NEET"] |
| `admission_start_date` | DATE | |
| `admission_end_date` | DATE | |
| `merit_based` | BOOLEAN | DEFAULT FALSE |
| `direct_admission` | BOOLEAN | DEFAULT FALSE |
| `management_quota_seats` | INT | DEFAULT 0 |
| `nri_quota_seats` | INT | DEFAULT 0 |
| `library` | BOOLEAN | |
| `sports_facilities` | JSON / LONGTEXT | |
| `labs` | JSON / LONGTEXT | |
| `auditorium` | BOOLEAN | |
| `cafeteria` | BOOLEAN | |
| `wifi` | BOOLEAN | |
| `medical_facility` | BOOLEAN | |
| `transport` | BOOLEAN | |
| `hostel_available` | BOOLEAN | |
| `hostel_type` | ENUM('boys','girls','both') | |
| `hostel_capacity` | INT | |
| `hostel_fee_annual` | DECIMAL(10,2) | |
| `mess_available` | BOOLEAN | |
| `mess_type` | ENUM('veg','non-veg','both') | |
| `ac_available` | BOOLEAN | |
| `meta_title` | VARCHAR(70) | SEO |
| `meta_description` | VARCHAR(160) | SEO |
| `meta_keywords` | TEXT | SEO |
| `og_image_url` | VARCHAR(255) | SEO |
| `canonical_url` | VARCHAR(255) | SEO |
| `schema_markup` | LONGTEXT / JSON | JSON-LD structured data |
| `noindex` | BOOLEAN | DEFAULT FALSE |

### `university_contacts`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `website_url` | VARCHAR(255) | |
| `email` | VARCHAR(255) | |
| `phone` | VARCHAR(50) | |
| `address` | TEXT | Full address |
| `latitude` | DECIMAL(9,6) / DECIMAL(10,8) | |
| `longitude` | DECIMAL(9,6) / DECIMAL(11,8) | |
| `pincode` | VARCHAR(10) / VARCHAR(20) | |
| `google_maps_embed_url` | VARCHAR(500) | |
| `nearest_railway_km` | FLOAT | |
| `nearest_airport_km` | FLOAT | |

### `university_content`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `about_text` | LONGTEXT | Detailed about section |
| `highlights_json` | JSON | Key highlights |
| `accreditations_json` | JSON | |
| `rankings_json` | JSON | |
| `awards_json` | JSON | |

### `university_courses`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `course_name` | VARCHAR(255) NOT NULL | e.g. "B.Tech Computer Science" |
| `course_level` | ENUM('UG','PG','Diploma','PhD','Certificate') | |
| `duration_years` | TINYINT | |
| `total_fee` | DECIMAL(10,2) | Total course fee in INR |
| `semester_fee` | DECIMAL(10,2) | |
| `annual_fee` | DECIMAL(10,2) | |
| `seats_available` | INT | |
| `fee_last_updated` | DATE | |
| `specializations` | JSON | e.g. ["AI/ML","Data Science"] |
| `eligibility_criteria` | TEXT | |
| `application_fee` | DECIMAL(8,2) | |
| `emi_available` | BOOLEAN | DEFAULT FALSE |

### `university_placements`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `placement_year` | YEAR NOT NULL | |
| `avg_package_lpa` | DECIMAL(5,2) | Average package in LPA |
| `highest_package_lpa` | DECIMAL(5,2) | |
| `median_package_lpa` | DECIMAL(5,2) | |
| `placement_percentage` | FLOAT | % students placed |
| `students_placed` | INT | |
| `international_placements` | INT | |
| `top_recruiters` | JSON | e.g. ["Google","Microsoft","Amazon"] |
| `sector_wise_json` | JSON | |
| `placement_report_pdf` | VARCHAR(255) | |

### `university_admissions`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `admission_process` | TEXT | Step-by-step process |
| `accepted_exams` | JSON | e.g. ["JEE Main","BITSAT"] |
| `admission_start_date` | DATE | |
| `admission_end_date` | DATE | |
| `merit_based` | BOOLEAN | |
| `direct_admission` | BOOLEAN | |
| `management_quota_seats` | INT | |
| `nri_quota_seats` | INT | |
| `lateral_entry_available` | BOOLEAN | |
| `application_mode` | ENUM('online','offline','both') | |
| `selection_criteria` | TEXT | |

### `university_cutoffs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `exam_id` | CHAR(36) | FK → exams.id |
| `course_id` | CHAR(36) | FK → university_courses.id |
| `category` | ENUM('General','OBC','SC','ST','EWS','PwD') | |
| `year` | YEAR NOT NULL | |
| `opening_rank` | INT | |
| `closing_rank` | INT | |
| `round_number` | TINYINT | |
| `quota` | ENUM('AI','HS','OS','TF','PwD') | All India / Home State / Other State |
| `gender` | ENUM('neutral','female_only') | |

### `university_scholarships`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `scholarship_name` | VARCHAR(255) NOT NULL | |
| `scholarship_type` | ENUM('merit','need','sports','minority') | |
| `amount` | DECIMAL(10,2) | In INR |
| `amount_type` | ENUM('fixed','percentage','full_tuition') | |
| `eligibility_criteria` | TEXT | |
| `renewable` | BOOLEAN | |
| `apply_link` | VARCHAR(255) | URL to apply |

### `university_infrastructure`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `library` | BOOLEAN | |
| `library_books_count` | INT | |
| `sports_facilities` | JSON | |
| `labs` | JSON | |
| `auditorium` | BOOLEAN | |
| `auditorium_capacity` | INT | |
| `cafeteria` | BOOLEAN | |
| `wifi` | BOOLEAN | |
| `wifi_speed_mbps` | INT | |
| `medical_facility` | BOOLEAN | |
| `transport` | BOOLEAN | |
| `ev_charging` | BOOLEAN | |
| `solar_power` | BOOLEAN | |

### `university_hostels`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `hostel_available` | BOOLEAN | |
| `hostel_type` | ENUM('boys','girls','both','co-ed') | |
| `hostel_capacity` | INT | |
| `hostel_fee_annual` | DECIMAL(10,2) | |
| `mess_available` | BOOLEAN | |
| `mess_type` | ENUM('veg','non-veg','both') | |
| `ac_available` | BOOLEAN | |
| `room_types` | JSON | e.g. ["single","double","triple"] |
| `security_features` | JSON | |
| `laundry_available` | BOOLEAN | |

### `university_media` (gallery)
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `image_url` | VARCHAR(255) | For images: `uploads/media/xxx.jpg` |
| `image_type` | ENUM('campus','lab','hostel','event','classroom') | |
| `video_url` | VARCHAR(255) | YouTube embed URL |
| `video_type` | ENUM('tour','placement','event','alumni_talk') | |
| `document_url` | VARCHAR(255) | PDF paths |
| `document_type` | ENUM('brochure','prospectus','annual_report','ranking_cert') | |
| `360_tour_url` | VARCHAR(255) | Virtual tour URL |
| `virtual_tour_enabled` | BOOLEAN | |
| `caption` | VARCHAR(300) | |
| `sort_order` | TINYINT | |

### `university_faculty`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `faculty_name` | VARCHAR(200) NOT NULL | |
| `designation` | VARCHAR(255) | e.g. "Professor", "Assistant Professor" |
| `department` | VARCHAR(255) | e.g. "Computer Science" |
| `qualification` | VARCHAR(255) | e.g. "PhD IIT Bombay" |
| `experience_years` | TINYINT | |
| `photo_url` | VARCHAR(255) | Path: `uploads/faculty/xxx.jpg` |
| `research_papers` | INT | |
| `linkedin_url` | VARCHAR(255) | |
| `specialization` | VARCHAR(255) | |
| `phd_from` | VARCHAR(255) | |

### `university_faqs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `question_text` | TEXT NOT NULL | |
| `answer_text` | TEXT NOT NULL | |
| `category` | VARCHAR(255) | e.g. "Admissions", "Fees", "Placements" |
| `sort_order` | INT | DEFAULT 0 |
| `is_active` | BOOLEAN | DEFAULT TRUE |
| `schema_faq_enabled` | BOOLEAN | For SEO FAQ schema |

### `university_accreditations`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `university_id` | CHAR(36) NOT NULL | FK → universities.id |
| `accreditation_body` | VARCHAR(255) NOT NULL | e.g. "NAAC", "NBA", "UGC" |
| `accreditation_grade` | VARCHAR(50) | e.g. "A++", "A+" |
| `accreditation_year` | YEAR | |
| `accreditation_valid_until` | DATE | |

---

## Colleges

### `colleges` (core)
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `name` | VARCHAR(300) NOT NULL | Full college name |
| `slug` | VARCHAR(300) UNIQUE NOT NULL | URL-friendly slug |
| `college_type` | ENUM('govt','private','deemed','autonomous') | |
| `ownership` | ENUM('central','state','private_trust','minority') | |
| `status` | ENUM('active','pending','archived','rejected') | DEFAULT 'pending' |
| `is_featured` | BOOLEAN | DEFAULT FALSE |
| `is_verified` | BOOLEAN | DEFAULT FALSE |
| `featured_order` | INT | DEFAULT 0 |
| `ranking_nirf` | INT | |
| `ranking_qs` | INT | |
| `ranking_times` | INT | |
| `city_id` | INT | FK → cities.id |
| `state_id` | INT | FK → states.id |
| `established_year` | YEAR | |
| `university_affiliation` | VARCHAR(255) | Affiliated university name |
| `university_id` | CHAR(36) | FK → universities.id |
| `data_quality_score` | TINYINT | |
| `autonomous` | BOOLEAN | |
| `naac_grade` | ENUM('A++','A+','A','B++','B+','B','C') | |
| `ugc_approved` | BOOLEAN | |
| `aicte_approved` | BOOLEAN | |
| `nba_approved` | BOOLEAN | |
| `total_students` | INT | |
| `total_faculty` | INT | |
| `campus_area_acres` | FLOAT | |
| `verification_status` | ENUM('unverified','pending','verified','disputed') | |
| `verified_by` | CHAR(36) | FK → users.id |
| `verified_at` | TIMESTAMP | |
| `rejection_reason` | TEXT | |
| `duplicate_of` | CHAR(36) | FK → colleges.id |
| `import_batch_id` | CHAR(36) | |
| `last_data_audit_at` | TIMESTAMP | |
| `archived_at` | TIMESTAMP | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | auto-update |
| `about_text` | LONGTEXT | |
| `highlights_json` | JSON | |
| `accreditations` | JSON | |
| `rankings_json` | JSON | |
| `awards_json` | JSON | |
| `admission_process` | TEXT | |
| `accepted_exams` | JSON | |
| `admission_start_date` | DATE | |
| `admission_end_date` | DATE | |
| `merit_based` | BOOLEAN | |
| `direct_admission` | BOOLEAN | |
| `management_quota_seats` | INT | |
| `nri_quota_seats` | INT | |
| `library` | BOOLEAN | |
| `sports_facilities` | JSON | |
| `labs` | JSON | |
| `auditorium` | BOOLEAN | |
| `cafeteria` | BOOLEAN | |
| `wifi` | BOOLEAN | |
| `medical_facility` | BOOLEAN | |
| `transport` | BOOLEAN | |
| `hostel_available` | BOOLEAN | |
| `hostel_type` | ENUM('boys','girls','both') | |
| `hostel_capacity` | INT | |
| `hostel_fee_annual` | DECIMAL(10,2) | |
| `mess_available` | BOOLEAN | |
| `mess_type` | ENUM('veg','non-veg','both') | |
| `ac_available` | BOOLEAN | |
| `meta_title` | VARCHAR(70) | SEO |
| `meta_description` | VARCHAR(160) | SEO |
| `meta_keywords` | TEXT | SEO |
| `og_image_url` | VARCHAR(255) | SEO |
| `canonical_url` | VARCHAR(255) | SEO |
| `schema_markup` | JSON | SEO |
| `publish_status` | ENUM('draft','published') | |
| `published_at` | TIMESTAMP | |
| `noindex` | BOOLEAN | |

### `college_contacts`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `website_url` | VARCHAR(255) | |
| `email` | VARCHAR(255) | |
| `phone` | VARCHAR(20) | |
| `address` | TEXT | |
| `latitude` | DECIMAL(9,6) | |
| `longitude` | DECIMAL(9,6) | |
| `pincode` | VARCHAR(10) | |
| `google_maps_embed_url` | VARCHAR(500) | |
| `nearest_railway_km` | FLOAT | |
| `nearest_airport_km` | FLOAT | |

### `college_courses`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `course_id` | CHAR(36) NOT NULL | FK → courses.id |
| `duration_years` | TINYINT | |
| `total_fee` | DECIMAL(10,2) | |
| `semester_fee` | DECIMAL(10,2) | |
| `annual_fee` | DECIMAL(10,2) | |
| `seats` | INT | |
| `specializations` | JSON | |
| `fee_last_updated` | DATE | |

### `college_placements`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `placement_year` | YEAR NOT NULL | |
| `avg_lpa` | DECIMAL(5,2) | |
| `highest_lpa` | DECIMAL(5,2) | |
| `median_lpa` | DECIMAL(5,2) | |
| `placed_pct` | FLOAT | |
| `top_recruiters` | JSON | |
| `students_placed` | INT | |
| `international_placements` | INT | |

### `college_cutoffs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `exam_id` | CHAR(36) NOT NULL | FK → exams.id |
| `course_id` | CHAR(36) NOT NULL | FK → courses.id |
| `cutoff_year` | YEAR NOT NULL | |
| `category` | ENUM('General','OBC','SC','ST','EWS','PWD') | |
| `round_number` | TINYINT | |
| `opening_rank` | INT | |
| `closing_rank` | INT | |

### `college_media` (gallery)
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `logo_url` | VARCHAR(255) | |
| `cover_image_url` | VARCHAR(255) | |
| `image_url` | VARCHAR(255) | For gallery images |
| `image_type` | ENUM('campus','lab','hostel','event','classroom') | |
| `video_url` | VARCHAR(255) | |
| `video_type` | ENUM('tour','placement','event','alumni_talk') | |
| `document_url` | VARCHAR(255) | |
| `document_type` | ENUM('brochure','prospectus','annual_report','ranking_cert') | |
| `360_tour_url` | VARCHAR(255) | |
| `virtual_tour_enabled` | BOOLEAN | |
| `caption` | VARCHAR(255) | |
| `sort_order` | TINYINT | |

### `college_faculty`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `faculty_name` | VARCHAR(255) NOT NULL | |
| `designation` | VARCHAR(150) | |
| `department` | VARCHAR(150) | |
| `qualification` | VARCHAR(255) | |
| `experience_years` | INT | |
| `photo_url` | VARCHAR(255) | |
| `research_papers` | INT | |

### `college_faqs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `question` | TEXT NOT NULL | |
| `answer` | TEXT NOT NULL | |
| `category` | VARCHAR(100) | |
| `sort_order` | INT | |
| `is_active` | BOOLEAN | |

### `college_scholarships`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `scholarship_name` | VARCHAR(255) NOT NULL | |
| `scholarship_type` | ENUM('merit','means','sports','reserved_category') | |
| `amount` | DECIMAL(10,2) | |
| `eligibility_criteria` | TEXT | |
| `renewable` | BOOLEAN | |

### `college_accreditations`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `college_id` | CHAR(36) NOT NULL | FK → colleges.id |
| `accreditation_body` | VARCHAR(255) NOT NULL | |
| `accreditation_grade` | VARCHAR(50) | |
| `accreditation_year` | YEAR | |
| `accreditation_valid_until` | DATE | |

---

## Shared / SEO

### `seo_meta`
| Column | Type | Notes |
|--------|------|-------|
| `id` | CHAR(36) PK | UUID |
| `entity_type` | VARCHAR(50) NOT NULL | "university" or "college" |
| `entity_id` | CHAR(36) NOT NULL | FK → universities.id or colleges.id |
| `meta_title` | VARCHAR(70) | |
| `meta_description` | VARCHAR(160) | |
| `og_image_url` | VARCHAR(255) | |
| `canonical_url` | VARCHAR(255) | |
| `schema_markup` | JSON | |
| `noindex` | BOOLEAN | |
| `breadcrumb_json` | JSON | |

---

## Notes for Scrapers

1. **IDs**: All entity IDs are UUIDs (CHAR(36)). Generate with any UUID v4 generator.
2. **Slugs**: Must be unique, lowercase, hyphenated. Derive from name: ` strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name))`.
3. **Image paths**: Store as relative paths like `uploads/universities/{timestamp}_logo_{filename}.png`. The app prepends the base URL.
4. **JSON fields**: Store as JSON-encoded strings. Examples:
   - `specializations`: `["AI/ML", "Data Science", "Cybersecurity"]`
   - `top_recruiters`: `["Google", "Microsoft", "Amazon", "TCS"]`
   - `accepted_exams`: `["JEE Main", "BITSAT", "VITEEE"]`
5. **Fees**: All amounts in INR (Indian Rupees). LPA = Lakhs Per Annum.
6. **Status**: New records should be `status='active'` and `publish_status='published'`.
7. **University vs College**: A college can be affiliated with a university via `colleges.university_id`. Universities are standalone.
8. **Logo/Cover**: For universities, stored directly in `universities.logo_url` and `universities.cover_image_url`. For colleges, stored in `college_media.logo_url` and `college_media.cover_image_url`.
