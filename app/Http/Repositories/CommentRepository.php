<?php
/**
 * Created by PhpStorm.
 * User: lufficc
 * Date: 2016/8/19
 * Time: 17:41
 */
namespace App\Http\Repositories;

use App\Comment;
use App\Post;
use Illuminate\Http\Request;
use Lufficc\Mention;
use Parsedown;

/**
 * Class CommentRepository
 * @package App\Http\Repository
 */
class CommentRepository extends Repository
{
    static $tag = 'comment';
    protected $parseDown;
    protected $mention;

    /**
     * PostRepository constructor.
     * @param Mention $mention
     */
    public function __construct(Mention $mention)
    {
        $this->mention = $mention;
        $this->parseDown = new Parsedown();
    }

    public function model()
    {
        return app(Comment::class);
    }

    private function getCacheKey($commentable_type, $commentable_id)
    {
        return $commentable_type . '.' . $commentable_id . 'comments';
    }

    public function getByCommentable($commentable_type, $commentable_id)
    {
        $comments = $this->remember($this->getCacheKey($commentable_type, $commentable_id), function () use ($commentable_type, $commentable_id) {
            $commentable = app($commentable_type)->where('id', $commentable_id)->select(['id'])->firstOrFail();
            return $commentable->comments()->with(['user'])->get();
        });
        return $comments;
    }

    public function create(Request $request)
    {
        $this->clearCache();

        $comment = new Comment();
        $commentable_id = $request->get('commentable_id');
        $commentable = app($request->get('commentable_type'))->where('id', $commentable_id)->firstOrFail();

        $comment->content = $this->mention->parse($request->get('content'));
        $comment->html_content = $this->parseDown->text($comment->content);

        if (auth()->check()) {
            $user = auth()->user();
            $comment->user_id = $user->id;
            $comment->username = $user->name;
            $comment->email = $user->email;
        } else {
            $comment->username = $request->get('username');
            $comment->email = $request->get('email');
        }

        return $commentable->comments()->save($comment);
    }

    public function delete(Comment $comment)
    {
        $this->forget($this->getCacheKey($comment->commentable_type, $comment->commentable_id));
        return $comment->delete();
    }

    public function tag()
    {
        return CommentRepository::$tag;
    }

}