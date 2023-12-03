<?php

namespace App\Models;

class TiktokProfile
{
    private string $profileUrl;
    private int $totalViewCount;
    private string $profileLikeCount;
    private string $followerCount;
    private array $videoUrls;
    private int $videoCount;
    private array $videoLikes;

    public function __construct(
        string $profileUrl,
        int    $totalViewCount,
        string $profileLikeCount,
        string $followerCount,
        array  $videoUrls,
        int    $videoCount,
        array  $videoLikes
    )
    {
        $this->profileUrl = $profileUrl;
        $this->totalViewCount = $totalViewCount;
        $this->profileLikeCount = $profileLikeCount;
        $this->followerCount = $followerCount;
        $this->videoUrls = $videoUrls;
        $this->videoCount = $videoCount;
        $this->videoLikes = $videoLikes;
    }

    public function getProfileUrl(): string
    {
        return $this->profileUrl;
    }

    public function getTotalViewCount(): int
    {
        return $this->totalViewCount;
    }

    public function getProfileLikeCount(): string
    {
        return $this->profileLikeCount;
    }

    public function getFollowerCount(): string
    {
        return $this->followerCount;
    }

    public function getVideoUrls(): array
    {
        return $this->videoUrls;
    }

    public function getVideoCount(): int
    {
        return $this->videoCount;
    }

    public function getVideoLikes(): array
    {
        return $this->videoLikes;
    }
}
