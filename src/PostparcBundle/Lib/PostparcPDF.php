<?php

namespace PostparcBundle\Lib;

use TCPDF;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PostparcPDF.
 *
 * @author philg
 */
class PostparcPDF extends TCPDF
{
    //Page header
    public function _header($image_file)
    {
        // recherche extension fichier
        $path_parts = pathinfo($image_file);
        $fileExtension = $path_parts['extension'];
        switch ($fileExtension) {
            case 'jpeg':
            case 'jpg':
                $extension = 'JPEG';
                break;
            case 'png':
                $extension = 'PNG';
                break;
            case 'gif':
                $extension = 'GIF';
                break;
            default:
                $extension = 'JPEG';
                break;
        }
        if (file_exists($image_file)) {
            $size = getimagesize($image_file);

            $this->Image($image_file, 10, 10, $size[0] > 80 ? 80 : 0, 0, $extension, '', 'T', true, 300, '', false, false, 0, true, false, false);
        }

        // Set font
        //$this->SetY(5);
        //$this->SetFont('helvetica', 'B', 8);
        // Title
        //$this->Cell(0, 15, $image_file.' *****  '.$fileExtension, 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer()
    {
        // Position at 15 mm from bottom
        //$this->SetY(-15);
        // Set font
        //$this->SetFont('helvetica', 'I', 8);
        // Page number
        //$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function _footer($input)
    {
        $html = $input;
        $this->SetFont('helvetica', 'I', 8);
        $this->SetY(-15);
        $this->Cell(0, 10, $html, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
