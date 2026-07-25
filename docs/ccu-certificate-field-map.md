# CCU Visual Certificate Field Map

| Certificate Label | Database Key | Form Field | Source | Required |
| --- | --- | --- | --- | --- |
| Client | `client_name` | Client Name | CCU dynamic form | Yes |
| Client address line | `client_address` | Client Address | CCU dynamic form | Optional |
| Location of inspection | `location_line_1`, `location_line_2` | Location of Inspection lines | CCU dynamic form | Line 1 yes |
| Certificate No. | `certificates.certificate_number` / `inspections.reference` | System generated or issue override | System | Yes |
| Customer Order No. | `customer_order_number` | Customer Order Number | CCU dynamic form | Optional, reference allows blank |
| Date of examination | `date_of_examination` | Date of Examination | CCU dynamic form / inspection date | Yes |
| Next due date | `next_due_date` | Next Due Date | CCU dynamic form / inspection due date | Yes |
| Unit ID Number | `unit_id_number` | Unit ID Number | CCU dynamic form | Yes |
| Asset No. | `asset_number` | Asset Number | CCU dynamic form | Optional |
| Unit Qty | `unit_quantity` | Unit Quantity | CCU dynamic form | Yes |
| Description of Unit | `description_of_unit` | Description of Unit | CCU dynamic form | Yes |
| Unit Tare Weight | `unit_tare_weight` | Tare Weight | CCU dynamic form | Optional |
| Unit SWL Payload | `unit_swl_payload` | SWL / Payload | CCU dynamic form | Yes |
| Unit Gross Weight | `unit_gross_weight` | Gross Weight | CCU dynamic form | Yes |
| Sling ID | `sling_id` | Sling ID | CCU dynamic form | Yes |
| Sling Quantity | `sling_quantity`, `sling_quantity_unit` | Sling Quantity / Unit | CCU dynamic form | Yes |
| Sling Details | `sling_details` | Sling Details | CCU dynamic form | Yes |
| Sling Tare | `sling_tare_na` | Sling Tare / N/A | CCU dynamic form | Optional |
| Sling SWL | `sling_swl` | Sling SWL | CCU dynamic form | Yes |
| Sling Gross | `sling_gross_na` | Sling Gross / N/A | CCU dynamic form | Optional |
| Shackle ID | `inspection_items.shackle_details[].shackle_id` | Shackle Details repeatable section | CCU repeatable form | Yes, at least one |
| Shackle Quantity | `inspection_items.shackle_details[].quantity`, `quantity_unit` | Shackle Details repeatable section | CCU repeatable form | Yes |
| Shackle Description | `inspection_items.shackle_details[].shackle_description` | Shackle Details repeatable section | CCU repeatable form | Yes |
| Shackle Tare | `inspection_items.shackle_details[].tare_na` | Shackle Details repeatable section | CCU repeatable form | Optional |
| Shackle SWL | `inspection_items.shackle_details[].swl` | Shackle Details repeatable section | CCU repeatable form | Yes |
| Shackle Gross | `inspection_items.shackle_details[].gross_na` | Shackle Details repeatable section | CCU repeatable form | Optional |
| Load Test Unit ID | `load_unit_id` | Load Test - Unit ID | CCU dynamic form | Yes |
| Tested By | `load_tested_by` | Load Test - Tested By | CCU dynamic form | Yes |
| Load Certificate No. | `load_test_certificate_number` | Load Test Certificate Number | CCU dynamic form | Yes |
| Proof Load Test | `load_proof_load_test` | Proof Load Test | CCU dynamic form | Yes/blank allowed when source blank |
| Test Date | `load_test_date` | Load Test Date | CCU dynamic form | Yes |
| Sling Test ID | `sling_test_sling_id` | Sling Test - Sling ID | CCU dynamic form | Yes |
| Sling Manufacturer | `sling_manufacturer` | Sling Manufacturer | CCU dynamic form | Yes |
| OEM Certificate No. | `sling_oem_certificate_number` | Sling OEM Certificate Number | CCU dynamic form | Yes |
| Sling Proof Load | `sling_proof_load_test` | Sling Proof Load Test | CCU dynamic form | Optional |
| Sling Test Date | `sling_test_date` | Sling Test Date | CCU dynamic form | Yes |
| Standards | `standards` | Standards | CCU dynamic form | Yes |
| Equipment Type | `equipment_type` | Equipment Type | CCU dynamic form | Yes |
| Contrast Media | `contrast_media` | Contrast Media | CCU dynamic form | Yes |
| Test Procedure | `test_procedure` | Test Procedure | CCU dynamic form | Yes |
| Pole Spacing | `pole_spacing` | Pole Spacing | CCU dynamic form | Optional |
| Indicator | `indicator` | Indicator | CCU dynamic form | Optional |
| Technique | `technique` | Technique | CCU dynamic form | Yes |
| Test Method | `test_method` | Test Method | CCU dynamic form | Yes |
| Test Result | `test_result` | Test Result | CCU dynamic form | Yes |
| Due Date | `inspection_due_date` / `next_due_date` | Inspection Due Date | CCU dynamic form | Yes |
| Demagnetization | `demagnetization` | Demagnetization | CCU dynamic form | Optional |
| Colour Code | `colour_code` | Colour Code | CCU dynamic form | Optional |
| Inspector | `inspector_name`, `inspector_qualifications` | Inspector fields | CCU dynamic form | Yes |
| Authenticator | `authenticator_name`, `authenticator_qualifications` | Authenticator fields | CCU dynamic form | Yes |
| Registered Address | Company constant | Company settings/static renderer data | System | Yes |
| Operational Address | Company constant | Company settings/static renderer data | System | Yes |
| Verification QR | `certificates.verification_token` | Generated QR | System | Yes |
