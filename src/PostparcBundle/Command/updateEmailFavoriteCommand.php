<?php

namespace PostparcBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class updateEmailFavoriteCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
                ->setName('postparc:updateEmailFavoriteCommand')
                ->setDescription('Update email favorite from email professional')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('<info>Mise à jour massif du mail préféré des contacts</info>');
        $total = 0;
        $batchSize = 250;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $emailPro = null;
        $persons = $em->getRepository('PostparcBundle:Person')->getPersonWithPfo();
        //$persons = $em->getRepository('PostparcBundle:Person')->findBy(['id'=>1630]);
        $output->writeln("Traitement de " . count($persons) . " personnes.");
        foreach ($persons as $person) {
            $emailPro = null;
            foreach ($person->getPfos() as $pfo) {
                if (!$emailPro) {
                    //récupère le premier mail pro
                    $emailPro = $pfo->getEmail();
                    // Ajouter l'email pro aux coordonnées personnelles s'il n'existe pas déjà
                    $this->addEmailIfNotExists($person, $emailPro, $em, $total);
                } else {
                    // Ajouter l'email pro aux autres coordonnées s'il n'existe pas déjà
                    $this->addEmailIfNotExists($pfo, $emailPro, $em, $total);
                }
            }
            if (($total % $batchSize) === 0) {
                $em->flush();
            }
            //$em->clear();//plante sur un index
        } //endfor person
        $em->flush();

        $output->writeln('<info>Il y a eu ' . $total . ' mise à jour </info>');
    }

    private function addEmailIfNotExists($entity, $email, $em, &$total) {
        if ($email === null) {
            return;
        }
        if (!$entity->hasPreferedEmail() && !$entity->checkPreferedEmail($email)) {
            echo $entity->getId() . "-";
            $entity->addPreferedEmail($email);
            $total++;
            $em->persist($entity);
        }
    }
}
