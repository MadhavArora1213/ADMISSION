<?php
/** Shared admin tab navigation for college sub-pages */
$college_id = $college_id ?? ($_GET['college_id'] ?? '');
$active_tab = $active_tab ?? basename($_SERVER['PHP_SELF'], '.php');
if (!$college_id) return;

$tabs = [
    'college_form'       => ['label' => 'Identity & Contact', 'href' => "college_form.php?id={$college_id}&tab=identity"],
    'college_form_about' => ['label' => 'About & Amenities', 'href' => "college_form.php?id={$college_id}&tab=about"],
    'college_form_seo'   => ['label' => 'SEO & Publish', 'href' => "college_form.php?id={$college_id}&tab=seo"],
    'college_courses'    => ['label' => 'Courses & Fees', 'href' => "college_courses.php?college_id={$college_id}"],
    'college_placements' => ['label' => 'Placements', 'href' => "college_placements.php?college_id={$college_id}"],
    'college_cutoffs'    => ['label' => 'Cutoffs', 'href' => "college_cutoffs.php?college_id={$college_id}"],
    'college_media'      => ['label' => 'Media & Gallery', 'href' => "college_media.php?college_id={$college_id}"],
    'college_faqs'       => ['label' => 'FAQs', 'href' => "college_faqs.php?college_id={$college_id}"],
    'college_faculty'    => ['label' => 'Faculty', 'href' => "college_faculty.php?college_id={$college_id}"],
    'college_scholarships'=> ['label' => 'Scholarships', 'href' => "college_scholarships.php?college_id={$college_id}"],
    'college_updates'    => ['label' => 'News & Updates', 'href' => "college_updates.php?college_id={$college_id}"],
    'college_qna'        => ['label' => 'Student Q&A', 'href' => "college_qna.php?college_id={$college_id}"],
];
?>
<div class="tabs-nav">
    <?php foreach ($tabs as $key => $tab):
        $isActive = ($active_tab === $key);
        if ($key === 'college_form' && $active_tab === 'college_form' && ($_GET['tab'] ?? 'identity') !== 'identity') {
            $isActive = false;
        }
        if ($key === 'college_form_about' && $active_tab === 'college_form' && ($_GET['tab'] ?? '') === 'about') {
            $isActive = true;
        }
        if ($key === 'college_form_seo' && $active_tab === 'college_form' && ($_GET['tab'] ?? '') === 'seo') {
            $isActive = true;
        }
    ?>
    <a href="<?= htmlspecialchars($tab['href']) ?>" class="tab-link<?= $isActive ? ' active' : '' ?>"><?= htmlspecialchars($tab['label']) ?></a>
    <?php endforeach; ?>
</div>
