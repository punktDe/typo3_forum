<?php
namespace Mittwald\Typo3Forum\Controller;

/*                                                                      *
 *  COPYRIGHT NOTICE                                                    *
 *                                                                      *
 *  (c) 2015 Mittwald CM Service GmbH & Co KG                           *
 *           All rights reserved                                        *
 *                                                                      *
 *  This script is part of the TYPO3 project. The TYPO3 project is      *
 *  free software; you can redistribute it and/or modify                *
 *  it under the terms of the GNU General Public License as published   *
 *  by the Free Software Foundation; either version 2 of the License,   *
 *  or (at your option) any later version.                              *
 *                                                                      *
 *  The GNU General Public License can be found at                      *
 *  http://www.gnu.org/copyleft/gpl.html.                               *
 *                                                                      *
 *  This script is distributed in the hope that it will be useful,      *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of      *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       *
 *  GNU General Public License for more details.                        *
 *                                                                      *
 *  This copyright notice MUST APPEAR in all copies of the script!      *
 *                                                                      */

use Mittwald\Typo3Forum\Domain\Factory\Forum\PostFactory;
use Mittwald\Typo3Forum\Domain\Model\Forum\Post;
use Mittwald\Typo3Forum\Domain\Model\Forum\Topic;
use Mittwald\Typo3Forum\Domain\Model\User\FrontendUser;
use Mittwald\Typo3Forum\Domain\Repository\Forum\AttachmentRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\ForumRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\PostRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\TopicRepository;
use Mittwald\Typo3Forum\Domain\Repository\Moderation\PostReportRepository;
use Mittwald\Typo3Forum\Service\AttachmentService;
use Mittwald\Typo3Forum\Utility\Localization;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;

class PostController extends AbstractController {

	/**
	 * @var AttachmentRepository
	 */
	protected $attachmentRepository;

	/**
	 * @var AttachmentService
	 */
	protected $attachmentService = NULL;


	/**
	 * @var ForumRepository
	 */
	protected $forumRepository;

	/**
	 * @var PostRepository
	 */
	protected $postRepository;

	/**
	 * @var PostFactory
	 */
	protected $postFactory;

	/**
	 * @var TopicRepository
	 */
	protected $topicRepository;


	/**
	 * @param AttachmentRepository $attachmentRepository
	 */
	public function injectAttachmentRepository(AttachmentRepository $attachmentRepository): void
	{
		$this->attachmentRepository = $attachmentRepository;
	}


	/**
	 * @param AttachmentService $attachmentService
	 */
	public function injectAttachmentService(AttachmentService $attachmentService): void
	{
		$this->attachmentService = $attachmentService;
	}


	/**
	 * @param ForumRepository $forumRepository
	 */
	public function injectForumRepository(ForumRepository $forumRepository): void
	{
		$this->forumRepository = $forumRepository;
	}


	/**
	 * @param PostReportRepository $postRepository
	 */
	public function injectPostRepository(PostReportRepository $postRepository): void
	{
		$this->postRepository = $postRepository;
	}


	/**
	 * @param TopicRepository $topicRepository
	 */
	public function injectTopicRepository(TopicRepository $topicRepository): void
	{
		$this->topicRepository = $topicRepository;
	}

    /**
     *  Listing Action.
     * @return void
     */
    public function listAction() {

        $showPaginate = FALSE;
        switch($this->settings['listPosts']){
            case '2':
                $dataset = $this->postRepository->findByFilter(intval($this->settings['widgets']['newestPosts']['limit']), array('crdate' => 'DESC'));
                $partial = 'Post/LatestBox';
                break;
            default:
                $dataset = $this->postRepository->findByFilter();
                $partial = 'Post/List';
                $showPaginate = TRUE;
                break;
        }
        $this->view->assign('showPaginate', $showPaginate);
        $this->view->assign('partial', $partial);
        $this->view->assign('posts',$dataset);
    }

	/**
	 * @param Post $post
	 * @return string
	 */
	public function addSupporterAction(Post $post) {
		/** @var FrontendUser $currentUser */
		$currentUser = $this->authenticationService->getUser();

		// Return if User not logged in or user is post author or user has already supported the post
		if ($currentUser === NULL || $currentUser->isAnonymous() || $currentUser === $post->getAuthor() || $post->hasBeenSupportedByUser($currentUser) || $post->getAuthor()->isAnonymous()) {
			return json_encode(['error' => TRUE, 'error_msg' => 'not_allowed']);
		}

		// Set helpfulCount for Author
		$post->addSupporter($currentUser);
		$this->postRepository->update($post);

		$post->getAuthor()->setHelpfulCount($post->getAuthor()->getHelpfulCount() + 1);
		$post->getAuthor()->increasePoints((int)$this->settings['rankScore']['gotHelpful']);
		$this->frontendUserRepository->update($post->getAuthor());

		$currentUser->increasePoints((int)$this->settings['rankScore']['markHelpful']);
		$this->frontendUserRepository->update($currentUser);

		// output new Data
        return json_encode([
            "error" => false,
            "add" => 0,
            "postHelpfulCount" => $post->getHelpfulCount(),
            "userHelpfulCount" => $post->getAuthor()->getHelpfulCount()
        ]);
	}

	/**
	 * @param Post $post
	 * @return string
	 */
	public function removeSupporterAction(Post $post) {
		/** @var FrontendUser $currentUser */
		$currentUser = $this->authenticationService->getUser();

		if (!$post->hasBeenSupportedByUser($currentUser)) {
			return json_encode(["error" => true, "error_msg" => "not_allowed"]);
		}

		// Set helpfulCount for Author
		$post->removeSupporter($currentUser);
		$this->postRepository->update($post);

		$post->getAuthor()->setHelpfulCount($post->getAuthor()->getHelpfulCount() - 1);
		$post->getAuthor()->decreasePoints((int)$this->settings['rankScore']['gotHelpful']);
		$this->frontendUserRepository->update($post->getAuthor());
		$currentUser->decreasePoints((int)$this->settings['rankScore']['markHelpful']);
		$this->frontendUserRepository->update($currentUser);

		// output new Data
		return json_encode([
		    "error" => false,
            "add" => 1,
            "postHelpfulCount" => $post->getHelpfulCount(),
            "userHelpfulCount" => $post->getAuthor()->getHelpfulCount()
        ]);
	}

	/**
	 * Show action for a single post. The method simply redirects the user to the
	 * topic that contains the requested post.
	 * This function is called by post summaries (last post link)
	 *
	 * @param Post $post The post
	 * @param Post $quote The Quote
	 * @param int $showForm ShowForm
	 * @return void
	 */
	public function showAction(Post $post, Post $quote = NULL, $showForm = 0) {
		// Assert authentication
		$this->authenticationService->assertReadAuthorization($post);

		$redirectArguments = ['topic' => $post->getTopic(), 'showForm' => $showForm];

		if (!empty($quote)) {
			$redirectArguments['quote'] = $quote;
		}
		$pageNumber = $post->getTopic()->getPageCount();
		if ($pageNumber > 1) {
			$redirectArguments['@widget_0'] = ['currentPage' => $pageNumber];
		}

		// Redirect to the topic->show action.
		$this->redirect('show', 'Topic', NULL, $redirectArguments);
	}

	/**
	 * Displays the form for creating a new post.
	 *
	 * @ignorevalidation $post
	 *
	 * @param Topic $topic The topic in which the new post is to be created.
	 * @param Post $post The new post.
	 * @param Post $quote An optional post that will be quoted within the bodytext of the new post.
	 * @return void
	 */
	public function newAction(Topic $topic, Post $post = NULL, Post $quote = NULL) {
	    $csrfToken = FormProtectionFactory::get('frontend')->generateToken('post_new');

		// Assert authorization
		$this->authenticationService->assertNewPostAuthorization($topic);

		// If no post is specified, create an optionally pre-filled post (if a
		// quoted post was specified).
		if ($post === NULL) {
			$post = ($quote !== NULL) ? $this->postFactory->createPostWithQuote($quote) : $this->postFactory->createEmptyPost();
		} else {
            $this->authenticationService->assertEditPostAuthorization($post);
        }

		$this->view->assignMultiple([
			'topic' => $topic,
			'post' => $post,
			'currentUser' => $this->frontendUserRepository->findCurrent(),
            'csrfToken' => $csrfToken
		]);
	}

	/**
	 * Creates a new post.
	 *
	 * @param Topic $topic The topic in which the new post is to be created.
	 * @param Post $post The new post.
     * @param string $csrfToken
	 * @param array $attachments File attachments for the post.
	 *
	 * @validate $post \Mittwald\Typo3Forum\Domain\Validator\Forum\PostValidator
	 * @validate $attachments \Mittwald\Typo3Forum\Domain\Validator\Forum\AttachmentPlainValidator
     *
     * @throws \Exception if csrf token isn't valid
	 */

	public function createAction(Topic $topic, Post $post, $csrfToken = '', array $attachments = []) {
	    if (!FormProtectionFactory::get('frontend')->validateToken($csrfToken, 'post_new')) {
	        throw new \Exception('CSRF validation not valid', 1502281334);
        }

		// Assert authorization
		$this->authenticationService->assertNewPostAuthorization($topic);

		// Create new post, add the new post to the topic and persist the topic.
		$this->postFactory->assignUserToPost($post);

		if (!empty($attachments)) {
			$attachments = $this->attachmentService->initAttachments($attachments);
			$post->setAttachments($attachments);
		}

		$topic->addPost($post);
		$this->topicRepository->update($topic);

		// All potential listeners (Signal-Slot FTW!)
		$this->signalSlotDispatcher->dispatch(Post::class, 'postCreated', ['post' => $post]);

		// Display flash message and redirect to topic->show action.
		$this->controllerContext->getFlashMessageQueue()->enqueue(
			new FlashMessage(Localization::translate('Post_Create_Success'))
		);
		$this->clearCacheForCurrentPage();

		$redirectArguments = ['topic' => $topic, 'forum' => $topic->getForum()];
		$pageNumber = $topic->getPageCount();
		if ($pageNumber > 1) {
			$redirectArguments['@widget_0'] = ['currentPage' => $pageNumber];
		}
		$this->redirect('show', 'Topic', NULL, $redirectArguments);
	}

	/**
	 * Displays a form for editing a post.
	 *
	 * @ignorevalidation $post
	 * @param Post $post The post that is to be edited.
	 * @return void
	 */
	public function editAction(Post $post) {
	    $csrfToken = FormProtectionFactory::get('frontend')->generateToken('post_edit');

		if ($post->getAuthor() != $this->authenticationService->getUser() || $post->getTopic()->getLastPost()->getAuthor() != $post->getAuthor()) {
			// Assert authorization
			$this->authenticationService->assertModerationAuthorization($post->getTopic()->getForum());
		}
		$this->view->assign('post', $post);
        $this->view->assign('csrfToken', $csrfToken);
	}

	/**
	 * Updates a post.
	 *
	 * @param Post $post The post that is to be updated.
     * @param string $csrfToken
	 * @param array $attachments File attachments for the post.
	 *
     * @throws \Exception if csrf token didn't match
	 *
     * @return void
	 */
	public function updateAction(Post $post, $csrfToken = '', array $attachments = []) {
	    if (!FormProtectionFactory::get('frontend')->validateToken($csrfToken, 'post_edit')) {
            throw new \Exception('CSRF validation not valid', 1502281357);
        }

		if ($post->getAuthor() != $this->authenticationService->getUser() || $post->getTopic()->getLastPost()->getAuthor() != $post->getAuthor()) {
			// Assert authorization
			$this->authenticationService->assertModerationAuthorization($post->getTopic()->getForum());
		}
		if (!empty($attachments)) {
			$attachments = $this->attachmentService->initAttachments($attachments);
			foreach ($attachments as $attachment) {
				$post->addAttachments($attachment);
			}
		}
		$this->postRepository->update($post);

		$this->signalSlotDispatcher->dispatch(Post::class, 'postUpdated',
			['post' => $post]);
		$this->controllerContext->getFlashMessageQueue()->enqueue(
			new FlashMessage(Localization::translate('Post_Update_Success'))
		);
		$this->clearCacheForCurrentPage();
		$this->redirect('show', 'Topic', NULL, ['topic' => $post->getTopic()]);
	}

	/**
	 * Displays a confirmation screen in which the user is prompted if a post
	 * should really be deleted.
	 *
	 * @param Post $post The post that is to be deleted.
	 * @return void
	 */
	public function confirmDeleteAction(Post $post) {
	    $csrfToken = FormProtectionFactory::get('frontend')->generateToken('post_delete');

		$this->authenticationService->assertDeletePostAuthorization($post);
		$this->view->assign('post', $post);
        $this->view->assign('csrfToken', $csrfToken);
    }

	/**
	 * Deletes a post.
	 *
	 * @param Post $post The post that is to be deleted.
     * @param string $csrfToken
     *
     * @throws \Exception if csrf token didn't match
	 * @return void
	 */
	public function deleteAction(Post $post, $csrfToken = '') {
	    if (!FormProtectionFactory::get('frontend')->validateToken($csrfToken, 'post_delete')) {
            throw new \Exception('CSRF validation not valid', 1502281538);
        }

		// Assert authorization
		$this->authenticationService->assertDeletePostAuthorization($post);

		// Delete the post.
		$postCount = $post->getTopic()->getPostCount();
		$this->postFactory->deletePost($post);
		$this->controllerContext->getFlashMessageQueue()->enqueue(
			new FlashMessage(Localization::translate('Post_Delete_Success'))
		);

		// Notify observers and clear cache.
		$this->signalSlotDispatcher->dispatch(
			Post::class,
			'postDeleted',
			['post' => $post]
		);
		$this->clearCacheForCurrentPage();

		// If there is still on post left in the topic, redirect to the topic
		// view. If we have deleted the last post of a topic (i.e. the topic
		// itself), redirect to the forum view instead.
		if ($postCount > 1) {
			$this->redirect('show', 'Topic', NULL, ['topic' => $post->getTopic()]);
		} else {
			$this->redirect('show', 'Forum', NULL, ['forum' => $post->getForum()]);
		}
	}

	/**
	 * Displays a preview of a rendered post text.
	 * @param string $text The content.
	 */
	public function previewAction() {

        $this->view->assign('text', 'MEIN GEILER TEXT');
	}

	/**
	 * Downloads an attachment and increase the download counter
	 * @param \Mittwald\Typo3Forum\Domain\Model\Forum\Attachment $attachment
	 */
	public function downloadAttachmentAction($attachment) {
        $attachment->increaseDownloadCount();
		$this->attachmentRepository->update($attachment);

		//Enforce persistence, since it will not happen regularly because of die() at the end
		$persistenceManager = $this->objectManager->get("TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager");
		$persistenceManager->persistAll();

        header('Content-type: ' . $attachment->getMimeType());
        header("Content-Type: application/download");
        header('Content-Disposition: attachment; filename="' . $attachment->getFilename() . '"');
		readfile($attachment->getAbsoluteFilename());
		die();
	}
}
