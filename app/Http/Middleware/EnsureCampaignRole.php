<?php

namespace App\Http\Middleware;

use App\Enums\CampaignRole;
use App\Models\Campaign;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict route access based on the user's role within a campaign
 *
 * @description Accepts a variadic list of allowed roles. Admins bypass
 * all campaign role checks. The campaign is resolved from route model binding.
 *
 * @example
 * Route::middleware(['auth', 'campaign.role:mj'])->group(...);
 * Route::middleware(['auth', 'campaign.role:mj,joueur'])->group(...);
 */
class EnsureCampaignRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $campaign = $request->route('campaign');

        if (! $campaign instanceof Campaign) {
            abort(404);
        }

        $user = $request->user();

        // Admins bypass campaign role checks
        if ($user?->isAdmin()) {
            return $next($request);
        }

        $userRole = $user?->roleInCampaign($campaign);

        if ($userRole === null) {
            abort(403, 'You are not a member of this campaign.');
        }

        $allowedRoles = array_map(
            fn (string $role) => CampaignRole::from($role),
            $roles,
        );

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'You do not have the required role for this action.');
        }

        return $next($request);
    }
}
