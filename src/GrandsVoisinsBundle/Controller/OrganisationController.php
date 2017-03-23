<?php

namespace GrandsVoisinsBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use GrandsVoisinsBundle\Entity\Organisation;
use GrandsVoisinsBundle\Entity\User;
use GrandsVoisinsBundle\Form\OrganisationType;
use GrandsVoisinsBundle\GrandsVoisinsConfig;
use SimpleExcel\SimpleExcel;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use VirtualAssembly\SemanticFormsBundle\SemanticFormsClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OrganisationController extends Controller
{
    var $property = [
      "type"                  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      "img"                   => 'http://xmlns.com/foaf/0.1/img',
      "batiment"              => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#building',
      "nom"                   => 'http://xmlns.com/foaf/0.1/name',
      "nomAdministratif"      => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#administrativeName',
      "membres"               => 'http://www.w3.org/ns/org#hasMember',
      "description"           => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#description',
      'topic_interest'        => 'http://xmlns.com/foaf/0.1/topic_interest',
      'conventionType'        => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#conventionType',
      'headOf'                => 'http://www.w3.org/ns/org#headOf',
      'employeesCount'        => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#employeesCount',
      'homepage'              => 'http://xmlns.com/foaf/0.1/homepage',
      'mbox'                  => 'http://xmlns.com/foaf/0.1/mbox',
      'depiction'             => 'http://xmlns.com/foaf/0.1/depiction',
      'room'                  => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#room',
      'arrivalDate'           => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#arrivalDate',
      'status'                => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#status',
      'proposedContribution'  => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#proposedContribution',
      'realisedContribution'  => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#realisedContribution',
      'phone'                 => 'http://xmlns.com/foaf/0.1/phone',
      'twitter'               => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#twitter',
      'linkedin'              => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#linkedin',
      'facebook'              => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#facebook',
      'volunteeringProposals' => 'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#volunteeringProposals'
    ];

    public function allAction(Request $request)
    {

        $organisationEntity = $this->getDoctrine()->getManager()->getRepository(
          'GrandsVoisinsBundle:Organisation'
        );
        $organisations      = $organisationEntity->findAll();

        //form pour l'organisation
        $organisation = new Organisation();
        $form         = $this->get('form.factory')->create(
          OrganisationType::class,
          $organisation
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //for the organisation
            $em = $this->getDoctrine()->getManager();

            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            $em->persist($organisation);
            try {
                $em->flush($organisation);
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash(
                  'danger',
                  "le nom de l'orgnanisation que vous avez saisi est déjà présent"
                );

                return $this->redirectToRoute('all_orga');
            }
            $organisation->setGraphURI(
              GrandsVoisinsConfig::PREFIX.$organisation->getId().'-org'
            );
            $em->flush();

            //TODO find a way to call teamAction in admin
            //for the user
            $user = new User();

            $user->setUsername($form->get('username')->getData());
            $user->setEmail($form->get('email')->getData());

            // Generate password.
            $tokenGenerator = $this->container->get(
              'fos_user.util.token_generator'
            );
            $randomPassword = substr($tokenGenerator->generateToken(), 0, 12);
            $user->setPassword(
              password_hash($randomPassword, PASSWORD_BCRYPT, ['cost' => 13])
            );

            $user->setSfUser($randomPassword);

            // Generate the token for the confirmation email
            $conf_token = $tokenGenerator->generateToken();
            $user->setConfirmationToken($conf_token);

            //Set the roles
            $user->addRole("ROLE_ADMIN");

            $user->setFkOrganisation($organisation->getId());

            // Save it.
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            try {
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                //removing the organization added before
                $em = $this->getDoctrine()->resetManager();
                $em->remove(
                  $em->getRepository('GrandsVoisinsBundle:Organisation')->find(
                    $organisation->getId()
                  )
                );
                $em->flush();
                $this->addFlash(
                  'danger',
                  "l'utilisateur saisi est déjà présent"
                );

                return $this->redirectToRoute('all_orga');
            }

            $organisation->setFkResponsable($user->getId());
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            $em->persist($organisation);
            try {
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                //removing the organization and the user added before
                $em = $this->getDoctrine()->resetManager();
                $em->remove(
                  $em->getRepository('GrandsVoisinsBundle:User')->find(
                    $user->getId()
                  )
                );
                $em->remove(
                  $em->getRepository('GrandsVoisinsBundle:Organisation')->find(
                    $organisation->getId()
                  )
                );
                $em->flush();
                $this->addFlash(
                  'danger',
                  "Problème lors de la mise à jour des champs, veuillez contacter un administrateur"
                );

                return $this->redirectToRoute('all_orga');
            }
            $url = $this->generateUrl(
              'fos_user_registration_confirm',
              array('token' => $conf_token),
              UrlGeneratorInterface::ABSOLUTE_URL
            );
            // send email to the new organization
            $this->get('GrandsVoisinsBundle.EventListener.SendMail')
              ->sendConfirmMessage(
                $user,
                GrandsVoisinsConfig::ORGANISATION,
                $url,
                $randomPassword,
                $organisation
              );

            // TODO Grant permission to edit same organisation as current user.
            // Display message.
            $this->addFlash(
              'success',
              'Un compte à bien été créé pour <b>'.
              $user->getUsername().
              '</b>. Un email a été envoyé à <b>'.
              $user->getEmail().
              '</b> pour lui communiquer ses informations de connexion.'
            );

            return $this->redirectToRoute('all_orga');
        }

        return $this->render(
          'GrandsVoisinsBundle:Organisation:home.html.twig',
          array(
            "tabOrga"             => GrandsVoisinsConfig::$buildings,
            "organisations"       => $organisations,
            "formAddOrganisation" => $form->createView(),
          )
        );
    }

    public function orgaExportAction()
    {
        $lines              = [];
        $sfClient           = $this->container->get('semantic_forms.client');
        $organisationEntity = $this->getDoctrine()->getManager()->getRepository(
          'GrandsVoisinsBundle:Organisation'
        );
        $organisations      = $organisationEntity->findAll();
        $columns            = [];

        foreach ($organisations as $organisation) {
            // Sparql request.
            $properties = $sfClient->uriProperties(
              $organisation->getGraphURI()
            );
            // We have key / pair values.
            $lines[] = $properties;
            // Save new columns if some are missing.
            $columns = array_unique(
              array_merge($columns, array_keys($properties))
            );
        }

        $output = [];
        // Rebuild array based on strict columns list.
        foreach ($lines as $incompleteLine) {
            $line = [];
            foreach ($columns as $key) {
                $line[$key] = isset($incompleteLine[$key]) ? $incompleteLine[$key] : '';
            }
            $output[] = $line;
        }

        // Append first lint.
        array_unshift($output, $columns);

        $excel = new SimpleExcel('csv');
        /** @var \SimpleExcel\Writer\CSVWriter $writer */
        $writer = $excel->writer;
        // Fill.
        $writer->setData(
          $output
        );
        $writer->setDelimiter(";");
        $writer->saveFile('LesGrandsVoisins-'.date('Y_m_d'));

        return $this->redirectToRoute('all_orga');
    }

    public function newOrganisationAction(Request $request, $orgaId = null)
    {
        $sfClient = $this->container->get('semantic_forms.client');

        /* @var $organisation \GrandsVoisinsBundle\Repository\OrganisationRepository */
        // questionner la base pour savoir si l'orga est deja créer
        $organisationEntity = $this->getDoctrine()->getManager()->getRepository(
          'GrandsVoisinsBundle:Organisation'
        );
        $orgaId             = ($orgaId != null && $this->getUser()->getRoles(
            'SUPER_ADMIN'
          )) ? $orgaId : $this->GetUser()->getFkOrganisation();
        /* @var $organisation \GrandsVoisinsBundle\Entity\Organisation */
        $organisation = $organisationEntity->findOneById(
          $orgaId
        );

        if (is_null($organisation->getSfOrganisation())) {
            $form = $sfClient->create(SemanticFormsClient::ORGANISATION);
            $edit = false;
        } else {
            $form = $sfClient->edit(
              $organisation->getSfOrganisation(),
              SemanticFormsClient::ORGANISATION
            );
            $edit = true;
        }
        if (!$form) {
            $this->addFlash(
              'danger',
              'Une erreur s\'est produite lors de l\'affichage du formulaire'
            );

            return $this->redirectToRoute('home');
        }


        // Picture for organization
        $organisationEntity = $this->getDoctrine()->getManager()->getRepository(
          'GrandsVoisinsBundle:Organisation'
        );

        /* @var $organisation \GrandsVoisinsBundle\Entity\Organisation */
        $organisation_picture = $organisationEntity->findOneById(
          $this->GetUser()->getFkOrganisation()
        );

        $picture = $this->createFormBuilder($organisation_picture)
          ->add(
            'OrganisationPicture',
            FileType::class,
            array('data_class' => null)
          )
          ->add(
            'oldPicture',
            HiddenType::class,
            array(
              'mapped' => false,
              'data'   => $organisation_picture->getOrganisationPicture(),
            )
          )
          ->getForm();

        $picture->handleRequest($request);

        if ($picture->isSubmitted() && $picture->isValid()) {
            if ($picture->get('oldPicture')->getData()) {
                $oldDir      = $this->get('GrandsVoisinsBundle.fileUploader')
                  ->getTargetDir(
                    $picture->get('oldPicture')->getData()
                  );
                $oldFileName = $picture->get('oldPicture')->getData();
                // Check if file exists to avoid all errors.
                if (is_file($oldDir.'/'.$oldFileName)) {
                    $this->get('GrandsVoisinsBundle.fileUploader')
                      ->remove($oldFileName);
                }
            }
            $organisation_picture->setOrganisationPicture(
              $this->get('GrandsVoisinsBundle.fileUploader')->upload(
                $organisation_picture->getOrganisationPicture()
              )
            );
            $em = $this->getDoctrine()->getManager();
            $em->persist($organisation_picture);
            $em->flush();

            return $this->redirectToRoute('detail_orga');
        }
        $form = $this->get('GrandsVoisinsBundle.formattingForm')->format($form);

        return $this->render(
          'GrandsVoisinsBundle:Organisation:organisation.html.twig',
          array(
            'organisation'        => $form,
            'edit'                => $edit,
            'id'                  => $orgaId,
            'graphURI'            => $organisation->getGraphURI(),
            'picture'             => $picture->createView(),
            'OrganisationPicture' => $organisation->getOrganisationPicture(),
            'building'            => GrandsVoisinsConfig::$buildingsSimple,
            'property'            => $this->property,
          )
        );
    }

    public function saveOrganisationAction()
    {
        $edit = $_POST["edit"];
        $id   = $_POST["id"];
        unset($_POST["edit"]);
        unset($_POST["id"]);

        $sfClient = $this
          ->container
          ->get('semantic_forms.client');
        $sfClient
          ->verifMember($_POST, $_POST["graphURI"], $_POST["uri"]);
        $info = $sfClient
          ->send(
            $_POST,
            $this->getUser()->getEmail(),
            $this->getUser()->getSfUser()
          );

        //TODO: a modifier pour prendre l'utilisateur courant !
        if ($info == 200) {
            if (!$edit) {
                $organisationEntity = $this->getDoctrine()
                  ->getManager()
                  ->getRepository('GrandsVoisinsBundle:Organisation');
                $query              = $organisationEntity->createQueryBuilder(
                  'q'
                )
                  ->update()
                  ->set('q.sfOrganisation', ':link')
                  ->where('q.id=:id')
                  ->setParameter('link', $_POST["uri"])
                  ->setParameter('id', $this->getUser()->getfkOrganisation())
                  ->getQuery();
                $query->getResult();
            }

            $this->addFlash(
              'success',
              'Les modifications ont bien été prises en compte.'
            );

            return $this->redirectToRoute(
              'detail_orga_edit',
              ['orgaId' => $id]
            );

        } else {
            $this->addFlash(
              'success',
              'Une erreur s\'est produite lors de la sauvegarde du formulaire'
            );

            return $this->redirectToRoute('organisation');
        }
    }

    public function orgaDeleteAction($orgaId)
    {
        $organisationRepository = $this->getDoctrine()
          ->getManager()
          ->getRepository('GrandsVoisinsBundle:Organisation');

        $organisation  = $organisationRepository->find($orgaId);
        $entityManager = $this->getDoctrine()->getManager();
        if (!$organisation) {
            // Display error message.
            $this->addFlash(
              'danger',
              'Organisation introuvable.'
            );
        } else {
            // Delete.
            $entityManager->remove($organisation);

            $entityManager
              ->getConnection()
              ->prepare(
                'DELETE FROM user WHERE fk_organisation = :id_organisation'
              )
              ->execute([':id_organisation' => $organisation->getId()]);

            $entityManager->flush();
            // Display success message.
            $this->addFlash(
              'success',
              'L\'organisation <b>'.
              $organisation->getName().
              '</b> a bien été supprimée.'
            );
        }

        return $this->redirectToRoute('all_orga');
    }


}
