<?php

function certificate_create_jpeg_pdf_landscape(string $path, string $jpg): void
{
    $data=file_get_contents($jpg); if($data===false) throw new RuntimeException('Unable to read certificate image.');
    $info=getimagesize($jpg); $width=(int)$info[0]; $height=(int)$info[1];
    $objects=[
        "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
        "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
        "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /XObject << /Im0 5 0 R >> >> /Contents 4 0 R >> endobj\n",
    ];
    $stream='q 842 0 0 595 0 0 cm /Im0 Do Q';
    $objects[]="4 0 obj << /Length ".strlen($stream)." >> stream\n$stream\nendstream endobj\n";
    $objects[]="5 0 obj << /Type /XObject /Subtype /Image /Width $width /Height $height /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($data)." >> stream\n$data\nendstream endobj\n";
    $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
    foreach($objects as $object){$offsets[]=strlen($pdf);$pdf.=$object;}
    $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";
    for($n=1;$n<=5;$n++)$pdf.=sprintf('%010d 00000 n ',$offsets[$n])."\n";
    $pdf.="trailer << /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    if(file_put_contents($path,$pdf)===false) throw new RuntimeException('Unable to archive PDF.');
    @unlink($jpg);
}
