<?php

namespace Minds\Core\Wire\Methods;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Payments;
use Minds\Entities;
use Minds\Entities\User;
use Minds\Helpers;

class Points implements MethodInterface
{
    private $amount;
    private $entity;
    private $recurring; // monthly
    private $timestamp;
    private $manager;
    private $repository;

    public function __construct($manager = null, $repository = null)
    {
        $this->manager = $manager ?: Core\Di\Di::_()->get('Wire\Manager');
        $this->repository = $manager ?: Core\Di\Di::_()->get('Wire\Repository');
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    public function setPayload($payload = [])
    {
        return $this;
    }

    public function setRecurring($recurring)
    {
        $this->recurring = $recurring;
        return $this;
    }

    /**
     * @param mixed $timestamp
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function create()
    {
        $user = $this->entity->getOwnerEntity();

        if ($this->recurring) {
            //persist on points subscription table
            $this->createSubscription($user);
            return;
        }

        $this->doTransaction($user);
    }

    public function refund()
    {
        // TODO: Implement refund() method.
    }

    private function createSubscription(User $user) {
        // cancel subscription first
        $this->cancelSubscription();

        $this->doTransaction($user);
    }

    private function cancelSubscription() {
        $repo = new Payments\Plans\Repository();
        $repo->setEntityGuid($this->entity->guid)
            ->setUserGuid(Core\Session::getLoggedInUser()->guid);
        $repo->getSubscription(('wire'))
            ->cancel('wire');

        $wires = $this->manager->get([
            'user_guid' => Core\Session::getLoggedInUser()->guid,
            'type' => 'sent',
            'order' => 'DESC'
        ]);

        if (count($wires) > 0) {
            // get last recurring wire
            foreach ($wires as $wire) {
                if ($wire->isRecurring() && $wire->isActive() && $wire->getMethod() == 'points') {
                    $wire->setActive(false)
                        ->save();
                    break;
                }
            }

        }
    }

    private function doTransaction(User $user)
    {
        $name = $this->recurring ? 'Wire (Recurring)' : 'Wire';
        if ($this->amount > (int) Helpers\Counters::get(Core\Session::getLoggedinUser()->guid, 'points', false)) {
            throw new \Exception('Not enough points');
        }
        Helpers\Wallet::createTransaction(Core\Session::getLoggedInUserGuid(), -$this->amount, $this->entity->guid,
            $name);
        $this->id = Helpers\Wallet::createTransaction($this->entity->owner_guid, $this->amount, $this->entity->guid,
            $name);

        $this->saveWire($user);
    }

    private function saveWire($user)
    {
        $timeCreated = date('Y-m-d H:i:s');
        $wire = (new Entities\Wire)
            ->setAmount($this->amount)
            ->setRecurring($this->recurring)
            ->setFrom(Core\Session::getLoggedInUser())
            ->setTo($user)
            ->setTimeCreated($timeCreated)
            ->setEntity($this->entity)
            ->setMethod('points');
        $wire->save();

        $repo = Di::_()->get('Wire\Repository');

        $repo->add($wire);
    }
}
