<?php

namespace Mosparo\ExpressionLanguage;

use Mosparo\Util\IpUtil;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class AllowListExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        $securityCompiler = function (array $data, $ipAddress, $allowList): string
        {
            return sprintf('empty(%2$s) || \Mosparo\Util\IpUtil::isIpAllowed(%1$s, %2$s)', $ipAddress, $allowList);
        };

        $securityEvaluator = function (array $data, string $ipAddress, string $allowList): bool
        {
            return IpUtil::isIpAllowed($ipAddress, $allowList);
        };

        $routingCompiler = function ($ipAddress, $allowList): string
        {
            return sprintf('empty(%2$s) || \Mosparo\Util\IpUtil::isIpAllowed(%1$s, %2$s)', $ipAddress, $allowList);
        };

        $routingEvaluator = function (string $ipAddress, string $allowList): bool
        {
            return IpUtil::isIpAllowed($ipAddress, $allowList);
        };

        return array(
            new ExpressionFunction('ip_on_allow_list_security', $securityCompiler, $securityEvaluator),
            new ExpressionFunction('ip_on_allow_list_routing', $routingCompiler, $routingEvaluator),
        );
    }
}