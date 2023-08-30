<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message;

use AssertionError;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\EventHandler\Participant;
use danog\MadelineProto\EventHandler\Participant\Left;
use danog\MadelineProto\EventHandler\Participant\Admin;
use danog\MadelineProto\EventHandler\Participant\Member;
use danog\MadelineProto\EventHandler\Participant\MySelf;
use danog\MadelineProto\EventHandler\Participant\Banned;
use danog\MadelineProto\EventHandler\Participant\Creator;

/**
 * Represents an incoming or outgoing channel message.
 */
final class ChannelMessage extends Message
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info
    ) {
        parent::__construct($API, $rawMessage, $info);
    }

    /**
     * Disable message signatures in channels
     *
     * @return boolean
     */
    public function disableSignatures(): bool
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleSignatures',
            [
                'channel' => $this->chatId,
                'enabled' => false,
            ]
        );
        return true;
    }

    /**
     * Enable message signatures in channels
     * 
     * @return boolean
     */
    public function enableSignatures(): bool
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.toggleSignatures',
            [
                'channel' => $this->chatId,
                'enabled' => true,
            ]
        );
        return true;
    }

    /**
     * Get info about a [channel/supergroup](https://core.telegram.org/api/channel) participant
     *
     * @param string|integer $member Participant to get info about.
     * @return Participant 
     * @throws AssertionError
     */
    public function getMember(string|int $member): Participant
    {
        $client = $this->getClient();
        $member = $client->getId($member);
        $result = $client->methodCallAsyncRead(
            'channels.getParticipant',
            [
                'channel' => $this->chatId,
                'participant' => $member,
            ]
        )['participant'];

        return match ($result['_']) {
            'channelParticipant' => new Member($result),
            'channelParticipantLeft' => new Left($client, $result),
            'channelParticipantSelf' => new MySelf($result),
            'channelParticipantAdmin' => new Admin($result),
            'channelParticipantBanned' => new Banned($client, $result),
            'channelParticipantCreator' => new Creator($result),
            default => throw new AssertionError("undefined Participant type: {$result['_']}")
        };
    }
}
