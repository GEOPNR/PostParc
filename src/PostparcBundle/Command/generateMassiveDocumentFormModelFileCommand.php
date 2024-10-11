<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use iio\libmergepdf\Merger;
use Symfony\Component\Process\Process;
use MBence\OpenTBSBundle\Services\OpenTBS;
use Symfony\Component\Console\Input\InputArgument;
use Cocur\Slugify\Slugify;

class generateMassiveDocumentFormModelFileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('postparc:generateMassiveDocumentFormModelFile')
                ->setDescription('background command for sending generted file to user')
                ->addArgument('filePathDir', InputArgument::REQUIRED, 'Path of the model document.')
                ->addArgument('fileName', InputArgument::REQUIRED, 'fileName.')
                ->addArgument('selectionDataEncoded', InputArgument::REQUIRED, 'json encoded selectionData.')
                ->addArgument('final_Extension', InputArgument::REQUIRED, 'final_Extension.')
                ->addArgument('userEmail', InputArgument::REQUIRED, 'email of the user.')
                ->addArgument('host', InputArgument::REQUIRED, 'host use to construct url.')
                ->addArgument('role', InputArgument::REQUIRED, 'role use for fields restrictions.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $output->writeln('<info>Démarrage Génération document via command</info>');
        $filePathDir = $input->getArgument('filePathDir');
        $final_Extension = $input->getArgument('final_Extension');
        $fileName = $input->getArgument('fileName');
        $userEmail = $input->getArgument('userEmail');
        $role = $input->getArgument('role');
        $selectionDataEncoded = $input->getArgument('selectionDataEncoded');
        $selectionData = json_decode($selectionDataEncoded, true);
        $filePath = $filePathDir . $fileName;
        $host = $input->getArgument('host');
        $slugify = new Slugify();

        $twig = $this->getContainer()->get('twig');
        $globals = $twig->getGlobals();
        $availableVariables = $globals['documentTemplate_availableFields'];

        $generatedFiles = [];

        // persons
        $output->writeln('Début traitement des <info>personnes</info>');
        if (isset($selectionData['personIds']) && count($selectionData['personIds'])) {
            $persons = $em->getRepository('PostparcBundle:Person')->getListForMassiveDocumentGeneration($selectionData['personIds']);
            $alreadyAdded = [];
            foreach ($persons as $person) {
                $object = $person['object'];
                $string = $object->__toString();
                if ($object->getCoordinate() && $object->getCoordinate()->getCity()) {
                    $string .= ' ' . $object->getCoordinate()->getCity();
                }
                $slug = $slugify->slugify($string);
                if (!in_array($slug, $alreadyAdded)) {
                    $generatedFiles[] = $this->generateFileFromModel($filePath, $person, $availableVariables, $filePathDir, $final_Extension, null, $role);
                    $alreadyAdded[] = $slug;
                }
            }
        }
        // envoi des fichiers concernant les personnes
        if (($generatedFiles !== []) > 0) {
            $this->sendFilesByMail($generatedFiles, $final_Extension, $userEmail, $host, 'persons');
            $output->writeln('<info>' . count($generatedFiles) . ' documents concernant les personnes ont été envoyés par mail</info>');
            $generatedFiles = [];
        }
        $output->writeln('Fin traitement des <info>personnes</info>');
        $output->writeln('------------------------------------------');

        // pfos
        $output->writeln('Début traitement des <info>pfos</info>');
        if (isset($selectionData['pfoIds']) && count($selectionData['pfoIds'])) {
            $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForMassiveDocumentGeneration($selectionData['pfoIds']);
            $alreadyAdded = [];
            foreach ($pfos as $pfo) {
                $object = $pfo['object'];
                $string = $object->__toString();
                if ($object->getCoordinate() && $object->getCoordinate()->getCity()) {
                    $string .= ' ' . $object->getCoordinate()->getCity();
                }
                $slug = $slugify->slugify($string);
                if (!in_array($slug, $alreadyAdded)) {
                    $generatedFiles[] = $this->generateFileFromModel($filePath, $pfo, $availableVariables, $filePathDir, $final_Extension);
                    $alreadyAdded[] = $slug;
                }
            }
        }
        // envoi des fichiers concernant les pfos
        if (($generatedFiles !== []) > 0) {
            $this->sendFilesByMail($generatedFiles, $final_Extension, $userEmail, $host, 'pfos');
            $output->writeln('<info>' . count($generatedFiles) . ' documents concernant les pfos ont été envoyés par mail</info>');
            $generatedFiles = [];
        }
        $output->writeln('Fin traitement des <info>pfos</info>');
        $output->writeln('-----------------------------------');

        // organizations
        $output->writeln('Début traitements des <info>organizations</info>');
        if (isset($selectionData['organizationIds']) && count($selectionData['organizationIds'])) {
            $organizations = $em->getRepository('PostparcBundle:Organization')->getListForMassiveDocumentGeneration($selectionData['organizationIds']);
            $alreadyAdded = [];
            foreach ($organizations as $organization) {
                $object = $organization['object'];
                $string = $object->getName();
                if ($object->getCoordinate() && $object->getCoordinate()->getCity()) {
                    $string .= ' ' . $object->getCoordinate()->getCity();
                }
                $slug = $slugify->slugify($string);
                if (!in_array($slug, $alreadyAdded)) {
                    $generatedFiles[] = $this->generateFileFromModel($filePath, $organization, $availableVariables, $filePathDir, $final_Extension);
                    $alreadyAdded[] = $slug;
                }
            }
        }
        // envoi des fichiers concernant les organizations
        if (($generatedFiles !== []) > 0) {
            $this->sendFilesByMail($generatedFiles, $final_Extension, $userEmail, $host, 'organizations');
            $output->writeln('<info>' . count($generatedFiles) . ' documents concernant les organizations ont été envoyés par mail</info>');
            $generatedFiles = [];
        }
        $output->writeln('Fin traitement des <info>organisations</info>');
        $output->writeln('--------------------------------------');

        // representations
        $output->writeln('Début traitements des <info>representations</info>');
        if (isset($selectionData['representationIds']) && count($selectionData['representationIds'])) {
            $representations = $em->getRepository('PostparcBundle:Representation')->getListForSelection($selectionData['representationIds'], isset($selectionData['personIds']) ? $selectionData['personIds'] : null, isset($selectionData['pfoIds']) ? $selectionData['pfoIds'] : null)->getResult();
            foreach ($representations as $representation) {
                $object = $representation['object'];
                if ($object->getPerson()) {
                    $persons = $em->getRepository('PostparcBundle:Person')->getListForMassiveDocumentGeneration([$object->getPerson()->getId()]);
                    $alreadyAdded = [];
                    foreach ($persons as $person) {
                        $objectPerson = $person['object'];
                        $string = $objectPerson->__toString();
                        if ($objectPerson->getCoordinate() && $objectPerson->getCoordinate()->getCity()) {
                            $string .= ' ' . $objectPerson->getCoordinate()->getCity();
                        }
                        $slug = $slugify->slugify($string);
                        if (!in_array($slug, $alreadyAdded)) {
                            $generatedFiles[] = $this->generateFileFromModel($filePath, $person, $availableVariables, $filePathDir, $final_Extension, $representation);
                            $alreadyAdded[] = $slug;
                        }
                    }
                } elseif ($object->getPfo()) {
                    $pfos = $em->getRepository('PostparcBundle:Pfo')->getListForMassiveDocumentGeneration([$object->getPfo()->getId()]);
                    $alreadyAdded = [];
                    foreach ($pfos as $pfo) {
                        $objectPfo = $pfo['object'];
                        $string = $objectPfo->__toString();
                        if ($objectPfo->getCoordinate() && $objectPfo->getCoordinate()->getCity()) {
                            $string .= ' ' . $objectPfo->getCoordinate()->getCity();
                        }
                        $slug = $slugify->slugify($string);
                        if (!in_array($slug, $alreadyAdded)) {
                            $generatedFiles[] = $this->generateFileFromModel($filePath, $pfo, $availableVariables, $filePathDir, $final_Extension, $representation);
                            $alreadyAdded[] = $slug;
                        }
                    }
                }
            }
        }
        if (($generatedFiles !== []) > 0) {
            $this->sendFilesByMail($generatedFiles, $final_Extension, $userEmail, $host, 'representations');
            $output->writeln('<info>' . count($generatedFiles) . ' documents concernant les representations ont été envoyés par mail</info>');
        }
        $output->writeln('Fin traitement des <info>representations</info>');
        $output->writeln('----------------------------------------');


        $output->writeln('<info>Fin Génération document via command</info>');
    }

    /**
     * generation des fichiers resultats a partir d'un modèle de fichier.
     *
     * @param type $filePath
     * @param type $scalar
     * @param type $availableVariables
     * @param type $filePathDir
     * @param type $final_Extension
     *
     * @return string
     */
    private function generateFileFromModel($filePath, $scalar, $availableVariables, $filePathDir, $final_Extension, $representation = null, $role = null)
    {
        //$TBS = $this->get('opentbs');
        $TBS = new OpenTBS();
        $TBS->setOption('noerr', true);
        $TBS->setOption('charset', false);
        // load your template
        $TBS->LoadTemplate($filePath);
        // allegement du tableau
        //unset($scalar['object']);

        if ($representation) {
            if ($representation->getOrganization()) {
                $TBS->MergeField('o_name', utf8_decode($representation->getOrganization()));
                $TBS->MergeField('o_abbreviation', utf8_decode($representation->getOrganization()->getAbbreviation()));
            }
            if ($representation->getPersonFunction()) {
                $personFunction = $representation->getPersonFunction();
                if ('female' == $representation->getSexe()) {
                    $personFunctionString = $personFunction->getWomenParticle() . ' ' . $personFunction->getWomenName();
                } else {
                    $personFunctionString = $personFunction->getMenParticle() . ' ' . $personFunction->getName();
                }
                $TBS->MergeField('rep_function', utf8_decode($personFunctionString));
            }
            if ($representation->getMandateType()) {
                $TBS->MergeField('mt_name', utf8_decode($representation->getMandateType()));
            }
            if ($representation->getCoordinateObject()) {
                $TBS->MergeField('coord_bloc', utf8_decode(preg_replace('#<br\s*?/?>#i', "\n", $representation->getCoordinateObject()->getFormatedAddress())));
            }
            // overide slug value with representation slug
            $scalar['slug'] = $representation->getSlug();
        }
        if(method_exists($scalar['object'], 'getOrganization')){
            $TBS->MergeField('o_name', utf8_decode($scalar['object']->getOrganization()));
            $TBS->MergeField('o_abbreviation', utf8_decode($scalar['object']->getOrganization()->getAbbreviation()));
        }
        

        // remplacement bloc coordinate
        if (array_key_exists('object', $scalar) && method_exists($scalar['object'], 'getCoordinate')) {
            if ($role && 'Person' == $scalar['object']->getClassName()) {
                // prise en compte des restrictions aux données personelles
                $restrictions = [];
                $em = $this->getContainer()->get('doctrine')->getManager();
                $personnalFieldsRestriction = $em->getRepository('PostparcBundle:PersonnalFieldsRestriction')->findOneBy(['entity' => $scalar['object']->getEntity()]);
                if ($personnalFieldsRestriction && array_key_exists($role, $personnalFieldsRestriction->getRestrictions())) {
                    $restrictions = $personnalFieldsRestriction->getRestrictions()[$role];
                }
                $TBS->MergeField('coord_bloc', utf8_decode(preg_replace('#<br\s*?/?>#i', "\n", $scalar['object']->getCoordinate()->getFormatedAddress($restrictions))));
            } else {
                $TBS->MergeField('coord_bloc', str_replace("?", "'", utf8_decode(preg_replace('#<br\s*?/?>#i', "\n", $scalar['object']->getCoordinate()->getFormatedAddress()))));
            }
        }
        
        // QRCODE
        $qrCodeService = $this->getContainer()->get('postparc_qrCodeService');
        $qrCodeFileInfos = $qrCodeService->generateVcardQrCode($scalar['object']);
        //dump($qrCodeFilePath);die;
        $TBS->PlugIn(OPENTBS_CHANGE_PICTURE, '#QR_CODE#', $qrCodeFileInfos['fileName']);

        foreach ($availableVariables as $documentVariable) {
            if (isset($scalar[$documentVariable])) {
                $val = $scalar[$documentVariable];
                $TBS->MergeField($documentVariable, utf8_decode($val));
            } else {
                $TBS->MergeField($documentVariable, '');
            }
        }

        $baseFileName = uniqid();
        if (array_key_exists('slug', $scalar) && strlen($scalar['slug']) > 0) {
            $baseFileName = $scalar['slug'];
        }
        $fileName = 'pdf' == $final_Extension ? $baseFileName . '.odt' : $baseFileName . '.' . $final_Extension;
        $tmpFolder = $filePathDir . '/../tmp/';
        //dump($tmpFolder);die;
        $output_file_name = $tmpFolder . $fileName;
        $TBS->Show(OPENTBS_FILE, $output_file_name);

        if ('pdf' == $final_Extension) {
            // conversion en pdf du document
            $this->convertOdtToPdf($tmpFolder, $fileName);

            return str_replace('.odt', '.pdf', $output_file_name);
        }
        //dd($output_file_name);
        return $output_file_name;
    }

    /**
     * envoi de l'archive générée ou du pdf mergé via mail.
     *
     * @param type $generatedFiles
     * @param type $final_Extension
     * @param type $userEmail
     */
    private function sendFilesByMail($generatedFiles, $final_Extension, $userEmail, $host, $contentType)
    {
        $translator = $this->getContainer()->get('translator');

        switch ($final_Extension) {
            case 'pdf':
                $finalFile = $this->mergePdfs($generatedFiles);
                break;
            default:
                $finalFile = $this->compressFiles($generatedFiles);
        }
        $finalFileName = basename($finalFile);
        //$absoluteUrl = str_replace('app_dev.php/','',$this->getContainer()->get('router')->generate('homepage', array(), UrlGeneratorInterface::ABSOLUTE_URL).'uploads/documents/models/tmp/'.$finalFileName);
        $absoluteUrl = 'https://' . $host . '/uploads/documents/models/tmp/' . $finalFileName;

        // envoi du mail
        $from = 'no-reply@postparc.fr';
        $noreplyEmails =  $this->getContainer()->getParameter('noreplyEmails');
        if($noreplyEmails && is_array($noreplyEmails)) {
            $from = $noreplyEmails[0];
        }
        
        $subject = $translator->trans('Selection.document.resultexportMailSubject')." [$contentType]";
        $body = $translator->trans('Selection.document.resultexportMailBody', ['%archive%' => $absoluteUrl]);
        $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($from)
                ->setTo($userEmail)
                ->setBody(
                    $body,
                    'text/html'
                );

        $this->getContainer()->get('mailer')->send($message);

        // suppression du fichier après envoi
//unlink($finalFile);
    }

    /**
     * function to transform an odt file to an pdf file.
     *
     * @param type $tmpFolder
     * @param type $fileName
     */
    private function convertOdtToPdf($tmpFolder, $fileName)
    {
        // conversion file
        //$command1 = 'cd ' . $tmpFolder . ' && /usr/bin/unoconv -vvv ' . $fileName;
        $command1 = 'cd ' . $tmpFolder . ' && /usr/bin/unoconv ' . $fileName;
        $process = new Process($command1);
        $process->setTimeout(3600);
        //$process->setIdleTimeout(3600);
        $process->run();

        $command2 = 'cd ' . $tmpFolder . ' && rm ' . $fileName;
        $process2 = new Process($command2);
        $process2->run();
    }

    /**
     * generate zip archive form list of files.
     *
     * @param type $generatedFiles
     *
     * @return type
     */
    private function compressFiles($generatedFiles)
    {
        $filePathDir = $this->getContainer()->get('kernel')->getRootDir() . '/../web/uploads/documents/models/';
        $tmpfolder = $filePathDir . '/tmp/';
        $globalFileName = uniqid() . '.zip';
        $command = 'cd ' . $tmpfolder . ' && /usr/bin/zip -j ' . $globalFileName . ' ' . implode(' ', $generatedFiles) . ' && rm ' . implode(' ', $generatedFiles);
        $process = new Process($command);
        $process->run();

        return $tmpfolder . $globalFileName;
    }

    /**
     * merge array of pdf files into one final pdf.
     *
     * @param array $generatedFiles
     *
     * @return string
     */
    private function mergePdfs($generatedFiles)
    {
        $filePathDir = $this->getContainer()->get('kernel')->getRootDir() . '/../web/uploads/documents/models/';
        $tmpfolder = $filePathDir . '/tmp/';
        $globalFileName = $tmpfolder . uniqid() . '.pdf';
        $m = new Merger();
        foreach ($generatedFiles as $filename) {
            $m->addFile($filename);
        }

        file_put_contents($globalFileName, $m->merge());

        // nettoyage des pdf
        foreach ($generatedFiles as $filename) {
            unlink($filename);
        }

        return $globalFileName;
    }

    private function cleanString($string)
    {
        return strip_tags(trim($string));
    }
}
