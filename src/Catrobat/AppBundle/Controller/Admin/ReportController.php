<?php

namespace Catrobat\AppBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ReportController extends Controller
{
    public function unreportAction(Request $request = null) {

        /* @var $object \Catrobat\AppBundle\Entity\UserComment */
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException();
        }

        $object->setIsReported(false);
        $this->admin->update($object);

        $this->addFlash('sonata_flash_success', 'Report ' . $object->getId() . ' removed from list');

        return new RedirectResponse($this->admin->generateUrl('list'));
    }

    public function deleteCommentAction(Request $request = null) {
        /* @var $object \Catrobat\AppBundle\Entity\UserComment */
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException();
        }
        $em = $this->getDoctrine()->getManager();
        $comment = $em->getRepository('AppBundle:UserComment')->find($object->getId());

        if (!$comment) {
            throw $this->createNotFoundException(
                'No comment found for this id ' . $object->getId());
        }
        $em->remove($comment);
        $em->flush();
        $this->addFlash('sonata_flash_success', 'Comment ' . $object->getId() . ' deleted');
        return new RedirectResponse($this->admin->generateUrl('list'));
    }

    public function bannAction(Request $request = null) {
      /* @var $object \Catrobat\AppBundle\Entity\UserComment */
      $object = $this->admin->getSubject();

      if (!$object) {
        throw new NotFoundHttpException();
      }
      $em = $this->getDoctrine()->getManager();
      $comment = $em->getRepository('AppBundle:UserComment')->find($object->getId());

      if (!$comment) {
        throw $this->createNotFoundException(
          'No comment found for this id ' . $object->getId());
      }

      $user = $em->getRepository('AppBundle:User')->find($comment->getUserId());

      if (!$user) {
        throw $this->createNotFoundException(
          'No user found for this id ' . $comment->getUserId());
      }

      $message = $user->ban();
      $reason = $request->query->get('reason');
      // $em->remove($comment);
      $em->flush();
      /*
         $mail_address = $user->getEmail();

         $mail_content = wordwrap($_GET['Message'], 70);
         $headers = "From: webmaster@catrob.at" . "\r\n";
         mail($mail_address, "Admin Message", $mail_content, $headers);
      */
      $this->addFlash('sonata_flash_success', 'User ' . $user->getUsername() . $message);
      $this->addFlash('sonata_flash_success', 'Message is: ' . _('mail_messages.banned_user.subject'));
      return new RedirectResponse($this->admin->generateUrl('list'));
    }
}