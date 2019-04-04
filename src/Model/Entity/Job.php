<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Job Entity
 *
 * @property int $id
 * @property int $process_id
 * @property int $video_id
 * @property string $name
 * @property string $command
 * @property int $status
 * @property $log
 * @property \Cake\I18n\FrozenTime $start
 * @property \Cake\I18n\FrozenTime $end
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 * @property \App\Model\Entity\Video $video
 */
class Job extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'process_id' => true,
        'video_id' => true,
        'name' => true,
        'command' => true,
        'status' => true,
        'log' => true,
        'start' => true,
        'end' => true,
        'created' => true,
        'modified' => true,
        'video' => true
    ];
}
