<?php

namespace PostparcBundle\Controller;

use PostparcBundle\Entity\City;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * imports controller.
 *
 * @Route("/imports")
 */
class ImportController extends Controller
{
    /**
     * index of import module
     *
     * @param Request $request
     * @Route("/", name="import_index", methods="GET|POST")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $currentEntityConfig = $request->getSession()->get('currentEntityConfig');
        // construction du formulaire
        $formObject = $this->createFormBuilder()
                ->add('persons', FileType::class, [
                  'label' => 'Import.fields.persons',
                  'required' => false
                ])
                ->add('searchCityByCP', CheckboxType::class, [
                  'label' => 'Import.fields.searchCityByCP',
                  'required' => false
                ])
                ->add('pfos', FileType::class, [
                  'label' => 'Import.fields.pfos',
                  'required' => false
                ])
                ->add('organizations', FileType::class, [
                  'label' => 'Import.fields.organizations',
                  'required' => false
                ])
                ->add('save', SubmitType::class, [
                  'label' => 'Import.actions.import',
                  'attr' => ['class' => 'btn btn-primary', 'id' => 'importSubmit']
                ]);
        if ($currentEntityConfig['use_representation_module']) {
            $formObject->add('representations', FileType::class, [
                  'label' => 'Import.fields.representations',
                  'required' => false
                ]);
        }
        $form =  $formObject->getForm();
        $form->handleRequest($request);
        $returnCommandContent = '';
        $isExecute = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $personsFile = $data['persons'];
            $pfosFile = $data['pfos'];
            $organizationsFile = $data['organizations'];
            $representationsFile = $data['representations'];
            $searchCityByCP = $data['searchCityByCP'];
            if ($personsFile) {
                $return = $this->importData($personsFile, 'import_person', 18, $searchCityByCP);
                $this->addFlash($return['status'], $return['message']);
                $returnCommandContent = $return['returnCommandContent'];
            }

            if ($pfosFile) {
                $return = $this->importData($pfosFile, 'import_pfo', 12, $searchCityByCP);
                $this->addFlash($return['status'], $return['message']);
                $returnCommandContent = $return['returnCommandContent'];
            }

            if ($organizationsFile) {
                $return = $this->importData($organizationsFile, 'import_organization', 16, $searchCityByCP);
                $this->addFlash($return['status'], $return['message']);
                $returnCommandContent = $return['returnCommandContent'];
            }

            if ($representationsFile) {
                $return = $this->importData($representationsFile, 'import_representation', 12, $searchCityByCP);
                $this->addFlash($return['status'], $return['message']);
                $returnCommandContent = $return['returnCommandContent'];
            }
            $isExecute = true;
        }

        return $this->render('import/index.html.twig', [
                  'form' => $form->createView(),
                  'returnCommandContent' => $returnCommandContent,
                  'isExecute' => $isExecute
        ]);
    }

    private function importData($file, $tableName, $nbColumns, $searchCityByCP)
    {
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');

        // controls
        $originalName = $file->getClientOriginalName();
        $extension = explode(".", $originalName)[1];

        if ('csv' !== $extension) {
            return [
              'status' => 'alert',
              'message' => 'bad file extension',
              'returnCommandContent' =>  $translator->trans('Import.flashs.badFileExtension')
            ];
        } else {
            // empty data in table
            $this->truncateTable($tableName);
            $importQuery = "INSERT INTO $tableName VALUES \n";
            $nbDateImported = 0;

            $separatorLines = '';
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle) {
                $i = 0;
                while (!feof($handle)) {
                    $buffer = fgets($handle);
                    $arr = explode(";", $buffer);

                    if ($i == 0 && $nbColumns !== count($arr) && count($arr) != 1) {
                        $message = str_replace('%nbColumns%', $nbColumns, $translator->trans('Import.flashs.notGoodNumberOfCols'));
                        return [
                          'status' => 'error',
                          'message' => $message,
                          'returnCommandContent' => $message
                        ];
                    } elseif ($i > 0 && $nbColumns === count($arr)) {
                        $separator = ',';
                        $insertValues = '(' . $i;
                        foreach ($arr as $value) {
                            $insertValues .= $separator . '"' . str_replace('"', '', $value) . '"';
                        }
                        $insertValues .= ")";
                        $importQuery .= $separatorLines . $insertValues;
                        $separatorLines = ',';
                        $nbDateImported++;
                    }
                    $i++;
                }
                fclose($handle);
                if ($nbDateImported > 0) {
                    // inject data un import table
                    $statement = $em->getConnection()->prepare($importQuery);
                    if ($statement->execute()) {
                        // launch command
                        $returnCommandContent = nl2br($this->launchCommand($tableName, $searchCityByCP));
                        return [
                          'status' => 'success',
                          'message' =>  str_replace(['%nbDateImported%','%tableName%'], $nbColumns, $translator->trans('Import.flashs.successReturnInfos')),
                          'returnCommandContent' => $returnCommandContent
                        ];
                    } else {
                        $message = $translator->trans('Import.flashs.somethinWentWrongDuringImportInTable') . " " . $tableName;
                        return [
                          'status' => 'error',
                          'message' => $translator->trans('Import.flashs.somethinWentWrongDuringImportInTable') . " " . $tableName,
                          'returnCommandContent' =>   $message
                        ];
                    }
                }
            }
        }
    }

    private function launchCommand($type, $searchCityByCP)
    {
        $kernel = $this->container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        switch ($type) {
            case 'import_person':
                $command = 'postparc:importPerson';
                break;
            case 'import_pfo':
                $command = 'postparc:importPfo';
                break;
            case 'import_organization':
                $command = 'postparc:importOrganization';
                break;
            case 'import_representation':
                $command = 'postparc:importRepresentation';
                break;
        }
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityId();
        $commandParams = [
           'command' => $command,
           'entityID' => $entityId,
           '--no-debug'  => true, 
        ];
        if($searchCityByCP) {
            $commandParams['searchCityByCP'] = true;
        }
        $input = new ArrayInput($commandParams);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            true // true for decorated
        );
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $converter = new AnsiToHtmlConverter();
        $content = $output->fetch();

        // return new Response(""), if you used NullOutput()
        return $converter->convert($content);
    }

    private function truncateTable($tableName)
    {
        $em = $this->getDoctrine()->getManager();
        $queryClean = "TRUNCATE " . $tableName;
        $statement = $em->getConnection()->prepare($queryClean);
        $statement->execute();
    }
}
