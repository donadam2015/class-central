<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Form\CourseType;

/**
 * Course controller.
 *  @Secure(roles="ROLE_ADMIN")
 */
class CourseController extends Controller
{
    /**
     * Lists all Course entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findAll();

        return $this->render('ClassCentralSiteBundle:Course:index.html.twig', array(
            'entities' => $entities
        ));
    }
    
    /**
     *  List all Course entities filtered by intiative
     */
    
    public function initiativeAction($initiative)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $initiative = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneByCode($initiative);
        
        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findByInitiative($initiative->getId());

        return $this->render('ClassCentralSiteBundle:Course:index.html.twig', array(
            'entities' => $entities
        ));
        
    }

    /**
     * Finds and displays a Course entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }
        
        $offerings = $em->getRepository('ClassCentralSiteBundle:Offering')->findByCourse($id);

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Course:show.html.twig', array(
            'entity'      => $entity,
            'offerings' => $offerings,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Course entity.
     *
     */
    public function newAction()
    {
        $entity = new Course();
        $form   = $this->createForm(new CourseType(), $entity);

        return $this->render('ClassCentralSiteBundle:Course:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Course entity.
     *
     */
    public function createAction()
    {
        $entity  = new Course();        
        $request = $this->getRequest();
        $form    = $this->createForm(new CourseType(), $entity);
        $form->bindRequest($request);      
        if ($form->isValid()) {                                  
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('course_show', array('id' => $entity->getId())));
            
        }

        return $this->render('ClassCentralSiteBundle:Course:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Course entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $editForm = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Course:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Course entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $editForm   = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bindRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('course_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Course:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Course entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Course entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('course'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     *
     * @param $id Row id for the course
     * @param $slug decsecriptive url for the course
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function moocAction($id, $slug)
    {

       $em = $this->getDoctrine()->getEntityManager();
       $courseId = intval($id);
       $course = $this->get('Cache')->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );
       if(!$course)
       {
           // render a error page
           return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
       }


       return $this->render(
           'ClassCentralSiteBundle:Course:mooc.html.twig',
           array('page' => 'home',
                 'course'=>$course,
                 'offeringTypes' => Offering::$types,
                 'offeringTypesOrder' => array('upcoming','ongoing','selfstudy','past')
       ));
    }

    /**
     * Retrieves the course details and offerings
     * @param $courseId
     */
    public function getCourseDetails($courseId, $em)
    {
        // Get the course first
        $courseEntity = $em->getRepository('ClassCentralSiteBundle:Course')->findOneById($courseId);
        if(!$courseEntity)
        {
            // Invalid course
            return null;
        }
        $courseDetails = $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray($courseEntity);
        // Course exists get all the offerings
        $courseDetails['offerings'] = $em->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds(array($courseId));

        return $courseDetails;
    }
}
