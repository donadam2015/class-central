<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/1/16
 * Time: 7:57 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Steps for Meet your next course button
 * Class NextCourseWizardController
 * @package ClassCentral\SiteBundle\Controller
 */
class NextCourseWizardController extends Controller
{

    /**
     * Step 1: Where users are asked to pick a subject
     * @param Request $request
     * @return Response
     */
    public function stepPickSubjectsAction(Request $request)
    {
        $cache = $this->get('cache');

        $subjectsController = new StreamController();
        $subjects = $cache->get('stream_list_count', array($subjectsController, 'getSubjectsList'),array($this->container));

        $childSubjects = array();
        foreach($subjects['parent'] as $parent)
        {
            if( !empty($subjects['children'][$parent['id']]))
            {
                foreach($subjects['children'][$parent['id']] as $child)
                {
                    $childSubjects[] = $child;
                }
            }
        }


        $html = $this->render('ClassCentralSiteBundle:NextCourse:picksubjects.html.twig',
            array(
                'subjects' => $subjects,
                'childSubjects' => $childSubjects,
                'followSubjectItem' => Item::ITEM_TYPE_SUBJECT
            ))
            ->getContent();

        $response = array(
            'modal' => $html,
        );

        return new Response( json_encode($response) );
    }

    public function stepPickProvidersAction(Request $request)
    {
        $insController = new InstitutionController();
        $insData = $insController->getInstitutions($this->container,true);

        $providerController = new InitiativeController();
        $providersData = $providerController->getProvidersList($this->container);

        $html = $this->render('ClassCentralSiteBundle:NextCourse:pickproviders.html.twig',
            array(
                'followInstitutionItem' => Item::ITEM_TYPE_INSTITUTION,
                'followProviderItem' => Item::ITEM_TYPE_PROVIDER,
                'institutions' => $insData['institutions'],
                'providers' => $providersData['providers'],
            ))
            ->getContent();

        $response = array(
            'modal' => $html,
        );

        return new Response( json_encode($response) );
    }


    public function stepLoadingScreenAction(Request $request)
    {
        $html = $this->render('ClassCentralSiteBundle:NextCourse:loadingscreen.html.twig',
            array(

            ))
            ->getContent();

        $response = array(
            'modal' => $html,
        );

        return new Response( json_encode($response) );
    }

    /**
     * Meet your next course page
     * @param Request $request
     */
    public function nextCourseAction(Request $request)
    {
        return $this->render('ClassCentralSiteBundle:NextCourse:meetyournextcourse.html.twig', array(

        ));
    }
}