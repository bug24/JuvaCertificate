# CCU field inventory

| Display Label | Field Key | Category | Version | Section | Required | Source | Duplicate Of | Used in PDF |
|---|---|---|---:|---|---|---|---|---|
| Client Name | `client_name` | CCU Visual Inspection | 1 (archived) | - | Yes | Archived schema | clients.name override | Yes |
| Equipment ID / Tag Number | `equipment_id` | CCU Visual Inspection | 1 (archived) | - | Yes | Archived schema | - | Yes |
| Inspection Date | `inspection_date` | CCU Visual Inspection | 1 (archived) | - | Yes | Archived schema | - | Yes |
| SWL / Capacity | `swl_capacity` | CCU Visual Inspection | 1 (archived) | - | No | Archived schema | - | Yes |
| Remarks | `remarks` | CCU Visual Inspection | 1 (archived) | - | No | Archived schema | - | Yes |
| Client Name | `client_name` | CCU Visual Inspection | 2 (archived) | summary | Yes | Archived schema | clients.name override | Yes |
| Client Address | `client_address` | CCU Visual Inspection | 2 (archived) | summary | No | Archived schema | clients.address override | Yes |
| Inspection Location | `inspection_location` | CCU Visual Inspection | 2 (archived) | summary | No | Archived schema | - | Yes |
| Equipment Description | `equipment_description` | CCU Visual Inspection | 2 (archived) | equipment | Yes | Archived schema | - | Yes |
| Equipment ID / Tag Number | `equipment_identifier` | CCU Visual Inspection | 2 (archived) | equipment | Yes | Archived schema | - | Yes |
| Manufacturer | `manufacturer` | CCU Visual Inspection | 2 (archived) | equipment | No | Archived schema | - | Yes |
| Date of Manufacture | `date_of_manufacture` | CCU Visual Inspection | 2 (archived) | equipment | No | Archived schema | - | Yes |
| Date of Previous Examination | `date_of_previous_examination` | CCU Visual Inspection | 2 (archived) | examination | No | Archived schema | - | Yes |
| Date of Current Examination | `date_of_current_examination` | CCU Visual Inspection | 2 (archived) | examination | Yes | Archived schema | - | Yes |
| SWL / WLL / Capacity | `safe_working_load` | CCU Visual Inspection | 2 (archived) | examination | No | Archived schema | - | Yes |
| Result Summary | `result_summary` | CCU Visual Inspection | 2 (archived) | decision | Yes | Archived schema | - | Yes |
| Defects Affecting Safety | `defects_affecting_safety` | CCU Visual Inspection | 2 (archived) | decision | No | Archived schema | - | Yes |
| Repairs / Tests Required | `repairs_or_tests_required` | CCU Visual Inspection | 2 (archived) | decision | No | Archived schema | - | Yes |
| Additional Remarks | `additional_remarks` | CCU Visual Inspection | 2 (archived) | remarks | No | Archived schema | - | Yes |
| Competent Person / Inspector Name | `inspector_name` | CCU Visual Inspection | 2 (archived) | signoff | Yes | Archived schema | users.name override | Yes |
| Inspector Qualification | `inspector_qualification` | CCU Visual Inspection | 2 (archived) | signoff | No | Archived schema | - | Yes |
| Next Due Date | `next_due_date` | CCU Visual Inspection | 2 (archived) | signoff | Yes | Archived schema | inspections.next_due_date | Yes |
| CCU / Container Number | `ccu_number` | CCU Visual Inspection | 2 (archived) | equipment | Yes | Archived schema | - | Yes |
| Unit Type | `unit_type` | CCU Visual Inspection | 2 (archived) | equipment | Yes | Archived schema | - | Yes |
| Owner / Operator | `owner_operator` | CCU Visual Inspection | 2 (archived) | equipment | No | Archived schema | - | Yes |
| Tare Weight | `tare_weight` | CCU Visual Inspection | 2 (archived) | equipment | No | Archived schema | - | Yes |
| Gross Weight | `gross_weight` | CCU Visual Inspection | 2 (archived) | equipment | No | Archived schema | - | Yes |
| Markings / Plate Details | `markings_plate_details` | CCU Visual Inspection | 2 (archived) | decision | No | Archived schema | - | Yes |
| Certificate Client Name Override | `client_name` | CCU Visual Inspection | 3 (active) | client | No | Active category schema | clients.name override | Yes |
| Certificate Client Address Override | `client_address` | CCU Visual Inspection | 3 (active) | client | No | Active category schema | clients.address override | Yes |
| Location of Inspection - Line 1 | `location_line_1` | CCU Visual Inspection | 3 (active) | client | Yes | Active category schema | - | Yes |
| Location of Inspection - Line 2 | `location_line_2` | CCU Visual Inspection | 3 (active) | client | No | Active category schema | - | Yes |
| Customer Order Number | `customer_order_number` | CCU Visual Inspection | 3 (active) | metadata | Yes | Active category schema | - | Yes |
| Unit ID Number | `unit_id_number` | CCU Visual Inspection | 3 (active) | unit | Yes | Active category schema | - | Yes |
| Asset Number | `asset_number` | CCU Visual Inspection | 3 (active) | unit | No | Active category schema | - | Yes |
| Unit Quantity | `unit_quantity` | CCU Visual Inspection | 3 (active) | unit | Yes | Active category schema | - | Yes |
| Description of Unit | `description_of_unit` | CCU Visual Inspection | 3 (active) | unit | Yes | Active category schema | - | Yes |
| Tare Weight | `unit_tare_weight` | CCU Visual Inspection | 3 (active) | unit | No | Active category schema | - | Yes |
| SWL / Payload | `unit_swl_payload` | CCU Visual Inspection | 3 (active) | unit | Yes | Active category schema | - | Yes |
| Gross Weight | `unit_gross_weight` | CCU Visual Inspection | 3 (active) | unit | Yes | Active category schema | - | Yes |
| Sling ID | `sling_id` | CCU Visual Inspection | 3 (active) | sling | Yes | Active category schema | - | Yes |
| Sling Quantity | `sling_quantity` | CCU Visual Inspection | 3 (active) | sling | Yes | Active category schema | - | Yes |
| Sling Quantity Unit | `sling_quantity_unit` | CCU Visual Inspection | 3 (active) | sling | Yes | Active category schema | - | Yes |
| Sling Details | `sling_details` | CCU Visual Inspection | 3 (active) | sling | Yes | Active category schema | - | Yes |
| Sling Tare / N/A | `sling_tare_na` | CCU Visual Inspection | 3 (active) | sling | No | Active category schema | - | Yes |
| Sling SWL | `sling_swl` | CCU Visual Inspection | 3 (active) | sling | Yes | Active category schema | - | Yes |
| Sling Gross / N/A | `sling_gross_na` | CCU Visual Inspection | 3 (active) | sling | No | Active category schema | - | Yes |
| Load Test - Unit ID | `load_unit_id` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Load Test - Tested By | `load_tested_by` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Load Test Certificate Number | `load_test_certificate_number` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Proof Load Test | `load_proof_load_test` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Load Test Date | `load_test_date` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Sling Test - Sling ID | `sling_test_sling_id` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Sling Manufacturer | `sling_manufacturer` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Sling OEM Certificate Number | `sling_oem_certificate_number` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Sling Proof Load Test / Dash | `sling_proof_load_test` | CCU Visual Inspection | 3 (active) | load_test | No | Active category schema | - | Yes |
| Sling Test Date | `sling_test_date` | CCU Visual Inspection | 3 (active) | load_test | Yes | Active category schema | - | Yes |
| Standards | `standards` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Equipment Type | `equipment_type` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Contrast Media | `contrast_media` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Test Procedure | `test_procedure` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Pole Spacing | `pole_spacing` | CCU Visual Inspection | 3 (active) | inspection | No | Active category schema | - | Yes |
| Indicator | `indicator` | CCU Visual Inspection | 3 (active) | inspection | No | Active category schema | - | Yes |
| Technique | `technique` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Test Method | `test_method` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Test Result | `test_result` | CCU Visual Inspection | 3 (active) | inspection | Yes | Active category schema | - | Yes |
| Demagnetization | `demagnetization` | CCU Visual Inspection | 3 (active) | inspection | No | Active category schema | - | Yes |
| Colour Code | `colour_code` | CCU Visual Inspection | 3 (active) | inspection | No | Active category schema | - | Yes |
| Inspector Name Override | `inspector_name` | CCU Visual Inspection | 3 (active) | signatures | No | Active category schema | users.name override | Yes |
| Inspector Qualifications | `inspector_qualifications` | CCU Visual Inspection | 3 (active) | signatures | Yes | Active category schema | - | Yes |
| Authenticator Name | `authenticator_name` | CCU Visual Inspection | 3 (active) | signatures | Yes | Active category schema | - | Yes |
| Authenticator Qualifications | `authenticator_qualifications` | CCU Visual Inspection | 3 (active) | signatures | Yes | Active category schema | - | Yes |
