<?php
declare(strict_types=1);
namespace app\model\hd;
use think\Model;

class HdVoteItem extends Model
{
    protected $name = 'hd_vote_item';
    protected $autoWriteTimestamp = false;

    public function records()
    {
        return $this->hasMany(HdVoteRecord::class, 'vote_item_id');
    }
}
