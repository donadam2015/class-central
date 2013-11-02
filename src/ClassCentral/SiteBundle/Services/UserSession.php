<?php

namespace  ClassCentral\SiteBundle\Services;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class UserSession
{
    private $securityContext;

    private $em;

    private $session;

    const MT_COURSE_KEY = 'mooc_tracker_courses';
    const MT_SEARCH_TERM_KEY = 'mooc_tracker_search_terms';
    const MT_REFERRAL_KEY = 'mooc_tracker_referral';
    const USER_RECENTLY_VIEWED = 'user_recently_view';
    const NEWSLETTER_USER_EMAIL = 'newsletter_user_email';


    public function __construct(SecurityContext $securityContext, Doctrine $doctrine, Session $session)
    {
        $this->securityContext = $securityContext;
        $this->em              = $doctrine->getManager();
        $this->session         = $session;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            // user has just logged in. Update the session
            $this->saveUserInformationInSession();

            // Update the last login time stamp
            $user = $event->getAuthenticationToken()->getUser();
            $user->setLastLogin(new \DateTime());
            $this->em->persist($user);
            $this->em->flush();
        }

        if ($this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED'))
        {
            // user has logged in using remember_me cookie
        }

        // do some other magic here
        // $user = $event->getAuthenticationToken()->getUser();

        // ...
    }

    public function saveUserInformationInSession()
    {
        $user = $this->securityContext->getToken()->getUser();

        // Get MOOC tracker courseIds
        $courseIds = array();
        foreach($user->getMoocTrackerCourses() as $moocTrackerCourse)
        {
            $courseIds[] = $moocTrackerCourse->getCourse()->getId();
        }
        $this->session->set(self::MT_COURSE_KEY,$courseIds);

        // Search terms from MOOC tracker
        $searchTerms = array();
        foreach($user->getMoocTrackerSearchTerms() as $moocTrackerSearchTerm)
        {
            $searchTerms[] = $moocTrackerSearchTerm->getSearchTerm();
        }
        $this->session->set(self::MT_SEARCH_TERM_KEY, $searchTerms);

    }

    /**
     * Checks whether the course has been added to MOOC tracker
     */
    public function isCourseAddedToMT($courseId)
    {
        $courseIds = $this->session->get(self::MT_COURSE_KEY);
        if(empty($courseIds))
        {
            return false;
        }
        return in_array($courseId, $courseIds);
    }

    /**
     * Checks whether the search term has been added to MOOC tracker
     * @param $searchTerm
     * @return bool
     */
    public function isSearchTermAddedToMT($searchTerm)
    {
        $searchTerms = $this->session->get(self::MT_SEARCH_TERM_KEY);
        if(empty($searchTerms))
        {
            return false;
        }
        return in_array($searchTerm,$searchTerms);
    }

    public function getMTCourses()
    {
        return $this->session->get(self::MT_COURSE_KEY);
    }

    public function getMTSearchTerms()
    {
        return $this->session->get(self::MT_SEARCH_TERM_KEY);
    }

    public function saveSignupReferralDetails($details)
    {
         $this->session->set(self::MT_REFERRAL_KEY,$details);
    }

    public function getSignupReferralDetails()
    {
        return $this->session->get(self::MT_REFERRAL_KEY);
    }

    public function clearSignupReferralDetails()
    {
        return $this->session->remove(self::MT_REFERRAL_KEY);
    }

    /**
     * Saves recently viewed courses in session
     * @param $courseId
     */
    public function saveRecentlyViewed($courseId)
    {
        $courses = $this->getRecentlyViewed();
        if(empty($courses))
        {
            $courses = array();
            $courses[] = $courseId;
        }
        else
        {
            // Remove the course is it already exists
            $pos = array_search($courseId,$courses);
            if(is_numeric($pos))
            {
                unset($courses[$pos]);
            }

            // Push the course at the head
            array_unshift($courses, $courseId);

            // Save 5 courses
            $courses = array_slice($courses,0,8);
        }

        $this->session->set(self::USER_RECENTLY_VIEWED,$courses);
    }

    public function getRecentlyViewed()
    {
        return $this->session->get(self::USER_RECENTLY_VIEWED);
    }

    public function setNewsletterUserEmail($email)
    {
        $this->session->set(self::NEWSLETTER_USER_EMAIL, $email);
    }

    public function getNewsletterUserEmail()
    {
        return $this->session->get(self::NEWSLETTER_USER_EMAIL);
    }

}
