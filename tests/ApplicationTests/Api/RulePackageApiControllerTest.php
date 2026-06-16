<?php

namespace Mosparo\Tests\ApplicationTests\Api;

class RulePackageApiControllerTest extends ApiWebTestCase
{
    const PUBLIC = 'mosparoPublicKey';
    const PRIVATE = 'mosparoPrivateKey';
    const RULE_PACKAGE_JSON = '{"lastUpdatedAt":"2026-01-14 19:00:00","refreshInterval":3600,"rules":[{"name":"Test-rule","description":"","type":"word","items":[{"uuid":"7a3faae0-5014-4e9d-b0b4-454d7c3de206","type":"text","value":"spam","rating":50},{"uuid":"a195410c-0d4e-4bd6-88f8-f9cf3c145859","type":"regex","value":"/s(e|o)o/","rating":0}],"spamRatingFactor":101,"uuid":"b128c35f-6545-4a3b-8a29-785ca40c00b8"},{"name":"Currency symbols","description":"","type":"unicodeBlock","items":[{"uuid":"47e3ef8d-5a57-4ef3-ac63-3b6801a6e531","type":"block","value":"CurrencySymbols","rating":2}],"spamRatingFactor":1,"uuid":"b815fd17-2108-4f48-a7ec-dbe7097862a8"}]}';
    const INVALID_RULE_PACKAGE_JSON = '{"invalid_lastUpdatedAt":"2026-01-14 19:00:00","refreshInterval":3600,"rules":[{"name":"Test-rule","description":"","type":"word","items":[{"uuid":"7a3faae0-5014-4e9d-b0b4-454d7c3de206","type":"text","value":"spam","rating":50},{"uuid":"a195410c-0d4e-4bd6-88f8-f9cf3c145859","type":"regex","value":"/s(e|o)o/","rating":0}],"spamRatingFactor":101,"uuid":"b128c35f-6545-4a3b-8a29-785ca40c00b8"},{"name":"Currency symbols","description":"","type":"unicodeBlock","items":[{"uuid":"47e3ef8d-5a57-4ef3-ac63-3b6801a6e531","type":"block","value":"CurrencySymbols","rating":2}],"spamRatingFactor":1,"uuid":"b815fd17-2108-4f48-a7ec-dbe7097862a8"}]}';

    public function testHashIndexWithoutCache(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/2/hash-index'; // Using rule package 2, because number 1 has a cache.
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseStatusCodeSame(205);
        $this->assertEquals('{"result":false,"noCache":true}', $client->getResponse()->getContent());
    }

    public function testHashIndex(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/1/hash-index';
        $response = $client->request('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $response->text();

        $this->assertStringContainsString('00000000-0000-0000-0000-100000000001::r/d690b6e8ea06d318a2d6ccfabf4b446e/', $content);
        $this->assertStringContainsString('00000000-0000-0000-0000-200000000000::i/6a132754798a7571b687d33798ed3fa9/', $content);
    }

    public function testHashIndexWhenOffsetIsOutOfBounds(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/1/hash-index';
        $data = ['offset' => '100000'];
        $response = $client->request('GET', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $response->text();
        $this->assertStringEndsWith('###END', $content);
    }

    public function testRulesWithoutCache(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('{"result":false,"noCache":true}', $client->getResponse()->getContent());
    }

    public function testRulesWithoutRules(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Check the rules
        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('{"result":true,"rules":[],"page":1,"totalPages":0}', $client->getResponse()->getContent());
    }

    public function testCreatingAndGettingRule(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule',
                    'data' => [
                        'uuid' => '4aebac52-aa9d-427f-98e0-1f035c513186',
                        'name' => 'Test rule',
                        'description' => 'Test description',
                        'type' => 'word',
                        'spamRatingFactor' => 2,
                    ],
                ]
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Check the rules
        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertTrue($data['result']);
        $this->assertIsArray($data['rules']);
        $this->assertNotEmpty($data['rules']);
        $this->assertNotEmpty($data['page']);
        $this->assertNotEmpty($data['totalPages']);

        $rule = $data['rules'][0];
        $this->assertIsArray($rule);
        $this->assertEquals($rule['uuid'], '4aebac52-aa9d-427f-98e0-1f035c513186');
        $this->assertEquals($rule['type'], 'word');
        $this->assertEquals($rule['name'], 'Test rule');
        $this->assertEquals($rule['description'], 'Test description');
        $this->assertEquals($rule['spamRatingFactor'], 2);
        $this->assertEquals($rule['numberOfItems'], null);
        $this->assertNotEmpty($rule['listRoute']);

        // Check the empty list of rule items
        $apiEndpoint = $rule['listRoute'];
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $this->assertEquals('{"result":true,"ruleItems":[],"page":1,"totalPages":1}', $content);
    }

    public function testCreatingAndGettingRuleItem(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule',
                    'data' => [
                        'uuid' => 'ba9967cb-1f25-4343-8ebe-be3d23daecce',
                        'name' => 'Test rule',
                        'description' => 'Test description',
                        'type' => 'word',
                        'spamRatingFactor' => 2,
                    ],
                ],
                [
                    'type' => 'store_rule_item',
                    'data' => [
                        'ruleUuid' => 'ba9967cb-1f25-4343-8ebe-be3d23daecce',
                        'uuid' => '2d459aa1-4377-4028-955b-d4ab3172a750',
                        'type' => 'text',
                        'value' => 'luxury watch',
                        'rating' => 10,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Check the rules
        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        foreach ($data['rules'] as $rule) {
            if ($rule['uuid'] === 'ba9967cb-1f25-4343-8ebe-be3d23daecce') {
                $apiEndpoint = $rule['listRoute'];
                break;
            }
        }

        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertTrue($data['result']);
        $this->assertIsArray($data['ruleItems']);
        $this->assertNotEmpty($data['ruleItems']);
        $this->assertNotEmpty($data['page']);
        $this->assertNotEmpty($data['totalPages']);

        $ruleItem = $data['ruleItems'][0];
        $this->assertIsArray($ruleItem);
        $this->assertEquals($ruleItem['uuid'], '2d459aa1-4377-4028-955b-d4ab3172a750');
        $this->assertEquals($ruleItem['type'], 'text');
        $this->assertEquals($ruleItem['value'], 'luxury watch');
        $this->assertEquals($ruleItem['rating'], 10);
    }

    public function testBatchWithoutTasks(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals('{"result":false,"error":true,"errorMessage":"Tasks undefined."}', $client->getResponse()->getContent());
    }

    public function testBatchInvalidUpdateRulePackageTask(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        // refreshInterval property is missing
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['errors']);
        $this->assertCount(1, $data['errors']);
        $this->assertStringStartsWith('Task data not valid: update_rule_package; Data:', $data['errors'][0]);
    }

    public function testBatchInvalidStoreRuleTask(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule',
                    'data' => [
                        'uuid' => '61674c78-ab52-4477-a367-a786fae7c12f',
                        'name' => 'Test rule',
                        'description' => 'Test description',
                        'type' => 'word',
                        'spamRatingFactor' => 2,
                        'invalidAdditionalProperty' => true,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['errors']);
        $this->assertCount(1, $data['errors']);
        $this->assertStringStartsWith('Task data not valid: store_rule; Data:', $data['errors'][0]);
    }

    public function testBatchInvalidStoreRuleItemTask(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule',
                    'data' => [
                        'uuid' => '7835a275-8160-4b84-b784-a487e3a58417',
                        'name' => 'Test rule',
                        'description' => 'Test description',
                        'type' => 'word',
                        'spamRatingFactor' => 2,
                    ],
                ],
                [
                    'type' => 'store_rule_item',
                    'data' => [
                        'ruleUuid' => '7835a275-8160-4b84-b784-a487e3a58417',
                        'uuid' => '9a21c7af-c139-462b-85f7-21e2c43c0a76',
                        // type property is missing
                        'value' => 'luxury watch',
                        'rating' => 10,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['errors']);
        $this->assertCount(1, $data['errors']);
        $this->assertStringStartsWith('Task data not valid: store_rule_item; Data:', $data['errors'][0]);
    }

    public function testBatchStoreRuleItemTaskWithNonExistingRule(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule_item',
                    'data' => [
                        'ruleUuid' => '00000000-0000-0000-0000-000000000000', // No rule for this UUID
                        'uuid' => '4ddf5ff4-6b81-4e08-ad5c-1fbf03699c7d',
                        'type' => 'text',
                        'value' => 'luxury watch',
                        'rating' => 10,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['errors']);
        $this->assertCount(1, $data['errors']);
        $this->assertEquals('Rule not available: 00000000-0000-0000-0000-000000000000', $data['errors'][0]);
    }

    public function testBatchRemoveRule(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule',
                    'data' => [
                        'uuid' => 'ad31a484-07ee-4dd4-9652-e8fd983e82fd',
                        'name' => 'Test rule',
                        'description' => 'Test description',
                        'type' => 'word',
                        'spamRatingFactor' => 2,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Get the rules to find the ID
        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $ruleId = null;
        foreach ($data['rules'] as $rule) {
            if ($rule['uuid'] === 'ad31a484-07ee-4dd4-9652-e8fd983e82fd') {
                $ruleId = $rule['id'];
                break;
            }
        }

        $this->assertIsInt($ruleId);

        // Remove the rule again
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'remove_rule',
                    'data' => [
                        'id' => $ruleId,
                    ],
                ],
            ]
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Check the rules to confirm the deletion of the rule
        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $foundRule = false;
        foreach ($data['rules'] as $rule) {
            if ($rule['uuid'] === 'ad31a484-07ee-4dd4-9652-e8fd983e82fd') {
                $foundRule = true;
                break;
            }
        }
        $this->assertFalse($foundRule);
    }

    public function testBatchRemoveRuleItem(): void
    {
        $client = static::createClient();

        // Create a rule to initialize the rule package cache
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'store_rule',
                    'data' => [
                        'uuid' => '9cb91730-d394-4ed5-8565-4bdf6f4ff07c',
                        'name' => 'Test rule',
                        'description' => 'Test description',
                        'type' => 'word',
                        'spamRatingFactor' => 2,
                    ],
                ],
                [
                    'type' => 'store_rule_item',
                    'data' => [
                        'ruleUuid' => '9cb91730-d394-4ed5-8565-4bdf6f4ff07c',
                        'uuid' => '8775a803-e6bc-4d80-85df-e91b73dd9440',
                        'type' => 'text',
                        'value' => 'luxury watch',
                        'rating' => 10,
                    ],
                ],
            ]
        ];
        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Get the rules to find the ID
        $apiEndpoint = '/api/v1/rule-package/2/rules';
        $client->jsonRequest('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $getRuleItemsApiEndpoint = null;
        foreach ($data['rules'] as $rule) {
            if ($rule['uuid'] === '9cb91730-d394-4ed5-8565-4bdf6f4ff07c') {
                $getRuleItemsApiEndpoint = $rule['listRoute'];
                break;
            }
        }

        $this->assertNotEmpty($getRuleItemsApiEndpoint);

        // Get the rule items for the rule to find the rule item ID
        $client->jsonRequest('GET', $getRuleItemsApiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($getRuleItemsApiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $ruleItemId = null;
        foreach ($data['ruleItems'] as $ruleItem) {
            if ($ruleItem['uuid'] === '8775a803-e6bc-4d80-85df-e91b73dd9440') {
                $ruleItemId = $ruleItem['id'];
                break;
            }
        }

        // Remove the rule item again
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'remove_rule_item',
                    'data' => [
                        'id' => $ruleItemId,
                    ],
                ],
            ]
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        // Check the rules to confirm the deletion of the rule
        $client->jsonRequest('GET', $getRuleItemsApiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($getRuleItemsApiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $foundRuleItem = false;
        foreach ($data['ruleItems'] as $ruleItem) {
            if ($ruleItem['uuid'] === '8775a803-e6bc-4d80-85df-e91b73dd9440') {
                $foundRuleItem = true;
                break;
            }
        }
        $this->assertFalse($foundRuleItem);
    }

    public function testBatchRemoveTasksWithInvalidTasks(): void
    {
        $client = static::createClient();

        // Remove the rule again
        $apiEndpoint = '/api/v1/rule-package/2/batch';
        $data = [
            'tasks' => [
                [
                    'type' => 'update_rule_package',
                    'data' => [
                        'lastUpdatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                        'refreshInterval' => 86400,
                    ],
                ],
                [
                    'type' => 'remove_rule',
                    'data' => [
                        // id property is missing
                    ],
                ],
                [
                    'type' => 'remove_rule_item',
                    'data' => [
                        // id property is missing
                    ],
                ],
            ]
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['errors']);
        $this->assertCount(2, $data['errors']);
        $this->assertEquals('Task data not valid: remove_rule; Data: []', $data['errors'][0]);
        $this->assertEquals('Task data not valid: remove_rule_item; Data: []', $data['errors'][1]);
    }

    public function testImportApiRequest(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 3,
            'rulePackageContent' => self::RULE_PACKAGE_JSON,
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['successful']);
        $this->assertFalse($data['verifiedHash']);

        // Verify the stored content in the rule package
        $apiEndpoint = '/api/v1/rule-package/3/hash-index';
        $response = $client->request('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $response->text();

        $this->assertStringContainsString('b128c35f-6545-4a3b-8a29-785ca40c00b8::r/0c717dfa4f8cd8b8b9dad4c950678bfe/', $content);
        $this->assertStringContainsString('b128c35f-6545-4a3b-8a29-785ca40c00b8::r/0c717dfa4f8cd8b8b9dad4c950678bfe/', $content);
        $this->assertStringContainsString('7a3faae0-5014-4e9d-b0b4-454d7c3de206::i/1ef4ad8efc33207a0ac153f920aeb82e/', $content);
        $this->assertStringContainsString('a195410c-0d4e-4bd6-88f8-f9cf3c145859::i/989257cd898247ad9cc35d5bd15100e0/', $content);
        $this->assertStringContainsString('47e3ef8d-5a57-4ef3-ac63-3b6801a6e531::i/60e5a760ed84d6239a144944d0d23ee8/', $content);
    }

    public function testImportApiRequestWithHashVerification(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 3,
            'rulePackageContent' => self::RULE_PACKAGE_JSON,
            'rulePackageHash' => hash('sha256', self::RULE_PACKAGE_JSON),
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['successful']);
        $this->assertTrue($data['verifiedHash']);

        // Verify the stored content in the rule package
        $apiEndpoint = '/api/v1/rule-package/3/hash-index';
        $response = $client->request('GET', $apiEndpoint, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, [], self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $content = $response->text();

        $this->assertStringContainsString('b128c35f-6545-4a3b-8a29-785ca40c00b8::r/0c717dfa4f8cd8b8b9dad4c950678bfe/', $content);
        $this->assertStringContainsString('b128c35f-6545-4a3b-8a29-785ca40c00b8::r/0c717dfa4f8cd8b8b9dad4c950678bfe/', $content);
        $this->assertStringContainsString('7a3faae0-5014-4e9d-b0b4-454d7c3de206::i/1ef4ad8efc33207a0ac153f920aeb82e/', $content);
        $this->assertStringContainsString('a195410c-0d4e-4bd6-88f8-f9cf3c145859::i/989257cd898247ad9cc35d5bd15100e0/', $content);
        $this->assertStringContainsString('47e3ef8d-5a57-4ef3-ac63-3b6801a6e531::i/60e5a760ed84d6239a144944d0d23ee8/', $content);
    }

    public function testImportApiRequestWithoutRequiredParameters(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageHash' => hash('sha256', self::RULE_PACKAGE_JSON),
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertEquals($data['errorMessage'], 'Required parameter missing.');
    }

    public function testImportApiRequestWithInvalidRulePackageId(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 30000, // Invalid ID
            'rulePackageContent' => self::RULE_PACKAGE_JSON,
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertEquals($data['errorMessage'], 'Rule package not found.');
    }

    public function testImportApiRequestInInvalidRulePackageType(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 4, // Rule package of type AUTOMATICALLY_FROM_FILE
            'rulePackageContent' => self::RULE_PACKAGE_JSON,
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertEquals($data['errorMessage'], 'Rule package type (AUTOMATICALLY_FROM_FILE) is not allowed.');
    }

    public function testImportApiRequestWithInvalidHash(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 3,
            'rulePackageContent' => self::RULE_PACKAGE_JSON,
            'rulePackageHash' => 'invalid-hash',
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertEquals($data['errorMessage'], 'The specified hash is invalid for the given content.');
    }

    public function testImportApiRequestWithEmptyRulePackage(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 3,
            'rulePackageContent' => '', // Empty rule package content
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertEquals($data['errorMessage'], 'Rule package content is empty.');
    }

    public function testImportApiRequestWithInvalidRulePackageContent(): void
    {
        $client = static::createClient();

        $apiEndpoint = '/api/v1/rule-package/import';
        $data = [
            'rulePackageId' => 3,
            'rulePackageContent' => self::INVALID_RULE_PACKAGE_JSON,
        ];

        $client->jsonRequest('POST', $apiEndpoint, $data, server: [
            'HTTP_AUTHORIZATION' => $this->generateAuthorizationHeader($apiEndpoint, $data, self::PUBLIC, self::PRIVATE)
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertEquals($data['errorMessage'], 'A general error occurred.');
    }
}