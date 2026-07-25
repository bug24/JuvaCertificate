<?php

declare(strict_types=1);

function category_dependency_summary(int $categoryId): array
{
    $stmt = db()->prepare("SELECT
        (SELECT COUNT(*) FROM inspections WHERE category_id = ?) AS inspection_count,
        (SELECT COUNT(*) FROM inspections WHERE category_id = ? AND status IN ('draft','correction')) AS draft_count,
        (SELECT COUNT(*) FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id WHERE i.category_id = ?) AS certificate_count,
        (SELECT MAX(version) FROM form_templates WHERE category_id = ? AND status='active') AS active_template_version,
        (SELECT COUNT(*) FROM form_templates WHERE category_id = ?) AS template_count,
        (SELECT COUNT(DISTINCT i.form_template_id) FROM inspections i WHERE i.category_id = ?) AS used_template_count");
    $stmt->execute([$categoryId,$categoryId,$categoryId,$categoryId,$categoryId,$categoryId]);
    $row = $stmt->fetch() ?: [];
    return [
        'inspection_count'=>(int)($row['inspection_count']??0),
        'draft_count'=>(int)($row['draft_count']??0),
        'certificate_count'=>(int)($row['certificate_count']??0),
        'active_template_version'=>$row['active_template_version']!==null?(int)$row['active_template_version']:null,
        'template_count'=>(int)($row['template_count']??0),
        'used_template_count'=>(int)($row['used_template_count']??0),
    ];
}
