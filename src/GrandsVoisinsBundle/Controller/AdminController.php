<?php

namespace GrandsVoisinsBundle\Controller;


use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use GrandsVoisinsBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class AdminController extends Controller
{

    public function homeAction()
    {
        return $this->render(
            'GrandsVoisinsBundle:Admin:home.html.twig',
            array(// ...
            )
        );
    }

    public function profileAction()
    {
        $userSfLink = $this->getUser()->getSfLink();
        $sfClient   = $this->container->get('semantic_forms.client');

        if (!$userSfLink) {
            $form = $sfClient->createFoaf('Person');
        } else {
            $form = $sfClient->getForm($userSfLink);
        }

        // If errors, form will be null.
        if ($form) {
            // decode the url in html name
            foreach ($form["fields"] as $field) {
                $form["htmlName"] = urldecode($field["htmlName"]);
            }
        }

        return $this->render(
            'GrandsVoisinsBundle:Admin:profile.html.twig',
            array(
                "form" => $form,
                "graphURI" => $this->getUser()->getGraphURI(),
            )
        );
    }

    public function profileSaveAction()
    {
        foreach ($_POST as $key => $value) {
            unset($_POST[$key]);
            $_POST[str_replace("_", '.', urldecode($key))] = $value;
        }

        $info = $this
            ->container
            ->get('semantic_forms.client')
            ->send($_POST,$this->getUser()->getEmail(),$this->getUser()->getSfUser());

        if ($info == 200) {
            // Get the main user entity.
            $userRepository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('GrandsVoisinsBundle:User');

            // Update sfLink.
            $userRepository
                ->createQueryBuilder('q')
                ->update()
                ->set('q.sfLink', ':link')
                ->where('q.id=:id')
                ->setParameter('link', $_POST["uri"])
                ->setParameter('id', $this->getUser()->getId())
                ->getQuery()
                ->execute();

            $this->addFlash(
                'success',
                'tous est <b>ok !</b>'
            );

            return $this->redirectToRoute('profile');
        } else {
            $this->addFlash(
                'success',
                'quelque chose est <b>nok ...</b>'
            );
        }

        return $this->redirectToRoute('sfProfile');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function teamAction(Request $request)
    {
        // Find all users.
        $userManager = $this->getDoctrine()->getManager()->getRepository(
            'GrandsVoisinsBundle:User'
        );
        $users       = $userManager->findBy(
            array('fkOrganisation' => $this->getUser()->getFkOrganisation())
        );

        $form = $this->get('form.factory')->create(UserType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get posted data of type user
            $data = $form->getData();

            // Generate password.
            $tokenGenerator = $this->container->get(
                'fos_user.util.token_generator'
            );
            $randomPassword = substr($tokenGenerator->generateToken(), 0, 12);
            $data->setPassword(
                password_hash($randomPassword, PASSWORD_BCRYPT, ['cost' => 13])
            );

            $data->setSfUser($randomPassword);

            // Generate the token for the confirmation email
            $conf_token = $tokenGenerator->generateToken();
            $data->setConfirmationToken($conf_token);

            //Set the roles
            $data->addRole($form->get('access')->getData());

            $data->setFkOrganisation($this->getUser()->getFkOrganisation());
            // Save it.
            $em = $this->getDoctrine()->getManager();
            $em->persist($data);
            try{
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('danger', "l'utilisateur saisi existe déjà");
                return $this->redirectToRoute('team');
            }

            //send email to the new user
            $body = "Bonjour ".$data->getUsername()." !<br><br>
                    Pour valider votre compte utilisateur, merci de vous rendre sur http://localhost:8000/register/confirm/".$conf_token.".<br><br>
                    Ce lien ne peut être utilisé qu'une seule fois pour valider votre compte.<br><br>
                    Nom de compte : ".$data->getUsername()."<br>
                    Mot de passe : ".$randomPassword."<br><br>
                    Cordialement,
                    L'équipe";
            $this->get('GrandsVoisinsBundle.EventListener.SendMail')
                ->sendConfirmMessage($data, $body);

            // TODO Grant permission to edit same organisation as current user.
            // Display message.
            $this->addFlash(
                'success',
                'Un compte à bien été créé pour <b>'.
                $data->getUsername().
                '</b>. Un email a été envoyé à <b>'.
                $data->getEmail().
                '</b> pour lui communiquer ses informations de connexion.'
            );

            // Go back to team page.
            return $this->redirectToRoute('team');
        }

        return $this->render(
            'GrandsVoisinsBundle:Admin:team.html.twig',
            array(
                'users'       => $users,
                'formAddUser' => $form->createView(),
            )
        );
    }

    public function userDeleteAction($userId)
    {
        /* @var $userManager \FOS\UserBundle\Doctrine\UserManager */
        $userManager = $this->get('fos_user.user_manager');
        $user        = $userManager->findUserBy(['id' => $userId]);

        if (!$user) {
            // Display error message.
            $this->addFlash(
                'danger',
                'Utilisateur introuvable.'
            );
        } else {
            // Delete.
            $userManager->deleteUser($user);
            // Display success message.
            $this->addFlash(
                'success',
                'Le compte de <b>'.
                $user->getUsername().
                '</b> a bien été supprimé.'
            );
        }

        return $this->redirectToRoute('team');
    }
}
