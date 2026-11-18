<?php

/**
 * This file is heavily based on the original file from web-auth/webauthn-symfony-bundle
 * https://github.com/web-auth/webauthn-symfony-bundle/blob/5.3.x/src/CredentialOptionsBuilder/ProfileBasedRequestOptionsBuilder.php
 *
 * The reason for this copy is to prevent user enumeration through the user identifier.
 *
 * MIT License
 *
 * Copyright (c) 2018-2022 Spomky-Labs
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Mosparo\Security\Webauthn;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\Bundle\CredentialOptionsBuilder\PublicKeyCredentialRequestOptionsBuilder;
use Webauthn\Bundle\Dto\ServerPublicKeyCredentialRequestOptionsRequest;
use Webauthn\Bundle\Policy\ClientOverridePolicy;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialUserEntity;

class AuthenticationOptionsBuilder implements PublicKeyCredentialRequestOptionsBuilder
{
    protected PublicKeyCredentialRequestOptionsFactory $publicKeyCredentialRequestOptionsFactory;

    protected SerializerInterface $serializer;

    protected ValidatorInterface $validator;

    protected ClientOverridePolicy $overridePolicy;

    public function __construct(PublicKeyCredentialRequestOptionsFactory $publicKeyCredentialRequestOptionsFactory, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->publicKeyCredentialRequestOptionsFactory = $publicKeyCredentialRequestOptionsFactory;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->overridePolicy = new ClientOverridePolicy();
    }

    public function getFromRequest(Request $request, ?PublicKeyCredentialUserEntity &$userEntity = null): PublicKeyCredentialRequestOptions
    {
        $format = $request->getContentTypeFormat();
        if ($format !== 'json') {
            throw new BadRequestHttpException('Only JSON content type allowed');
        }

        $content = $request->getContent();
        $optionsRequest = $this->getServerPublicKeyCredentialRequestOptionsRequest($content);

        // Apply override policy to determine effective values
        /** @var ?string $userVerification */
        $userVerification = $this->overridePolicy->getEffectiveValue(
            'user_verification',
            $optionsRequest->userVerification,
            null // Will fallback to profile
        );

        $extensions = $this->getEffectiveExtensions($optionsRequest);

        return $this->publicKeyCredentialRequestOptionsFactory->create(
            'default',
            [],
            $userVerification,
            $extensions
        );
    }

    private function getEffectiveExtensions(
        ServerPublicKeyCredentialRequestOptionsRequest $optionsRequest
    ): ?AuthenticationExtensions {
        if (! $this->overridePolicy->canOverride('extensions') || ! is_array($optionsRequest->extensions)) {
            return null; // Use profile extensions
        }

        $extensions = [];
        foreach ($optionsRequest->extensions as $name => $data) {
            $extensions[] = AuthenticationExtension::create($name, $data);
        }

        return AuthenticationExtensions::create($extensions);
    }

    private function getServerPublicKeyCredentialRequestOptionsRequest(
        string $content
    ): ServerPublicKeyCredentialRequestOptionsRequest {
        $data = $this->serializer->deserialize(
            $content,
            ServerPublicKeyCredentialRequestOptionsRequest::class,
            JsonEncoder::FORMAT,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException(implode("\n", $messages));
        }

        return $data;
    }
}
