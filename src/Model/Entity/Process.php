<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Process Entity
 *
 * @property int $id
 * @property int $job_id
 * @property int $process_id
 * @property int $child_process
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Job[] $jobs
 * @property \App\Model\Entity\Process[] $processes
 */
class Process extends Entity
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
        'job_id' => true,
        'process_id' => true,
        'child_process' => true,
        'created' => true,
        'modified' => true,
        'jobs' => true,
        'processes' => true
    ];
}
