<?php

namespace Api\Users\Services;

use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Api\Users\Events\UserWasCreated;
use Api\Users\Events\UserWasDeleted;
use Api\Users\Events\UserWasUpdated;
use Illuminate\Support\Facades\Gate;
use Api\Users\Repositories\UserRepository;
use Api\Users\Exceptions\UserNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserService
{
    private $userRepository;
    private $dispatcher;

    public function __construct(
        UserRepository $userRepository,
        Dispatcher $dispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->dispatcher = $dispatcher;
    }

    public function getAll($options = [])
    {
        return $this->userRepository->getWithCount($options);
    }

    public function getById($userId, array $options = [])
    {
        $user = $this->getRequestedUser($userId, $options);
        
        return $user;
    }

    public function create($data)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->create($data);

            $this->dispatcher->dispatch(new UserWasCreated($user));

            DB::commit();
            
            return $user;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function update($userId, array $data)
    {
        $user = $this->getRequestedUser($userId, ['select' => ['id']]);

        if (Gate::denies('update-user', $user)) {
            throw new AccessDeniedHttpException('Cannot update this user.');
        }

        try {
            DB::beginTransaction();

            $this->userRepository->update($user, $data);

            $this->dispatcher->dispatch(new UserWasUpdated($user));

            DB::commit();

            return $user;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function delete($userId)
    {
        $user = $this->getRequestedUser($userId);

        try {
            DB::beginTransaction();

            $this->userRepository->delete($userId);

            $this->dispatcher->dispatch(new UserWasDeleted($user));

            DB::commit();

            return $user;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function getRequestedUser($userId, array $options = [])
    {
        $user = $this->userRepository->getById($userId, $options);

        if (is_null($user)) {
            throw new UserNotFoundException;
        }

        return $user;
    }
}
