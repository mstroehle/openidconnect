<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\OpenIdConnect\Service;

use OC\HintException;
use OC\User\LoginException;
use OCA\OpenIdConnect\Client;
use OCP\IUser;
use OCP\IUserManager;

class UserLookupService {

	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var Client
	 */
	private $client;

	public function __construct(IUserManager $userManager,
								Client $client) {
		$this->userManager = $userManager;
		$this->client = $client;
	}

	/**
	 * @param mixed $userInfo
	 * @return IUser
	 * @throws LoginException
	 * @throws HintException
	 */
	public function lookupUser($userInfo): IUser {
		$openIdConfig = $this->client->getOpenIdConfig();
		if ($openIdConfig === null) {
			throw new HintException('Configuration issue in openidconnect app');
		}
		$searchByEmail = true;
		if (isset($openIdConfig['mode']) && $openIdConfig['mode'] === 'userid') {
			$searchByEmail = false;
		}
		$attribute = $openIdConfig['search-attribute'] ?? 'email';

		if ($searchByEmail) {
			$user = $this->userManager->getByEmail($userInfo->$attribute);
			if (!$user) {
				throw new LoginException("User with {$userInfo->$attribute} is not known.");
			}
			if (\count($user) !== 1) {
				throw new LoginException("{$userInfo->$attribute} is not unique.");
			}
			return $user[0];
		}
		$user = $this->userManager->get($userInfo->$attribute);
		if (!$user) {
			throw new LoginException("User {$userInfo->$attribute} is not known.");
		}
		return $user;
	}
}
