---
name: RACODE
description: A coastal chart for an Omani software studio, in Arabic and English.
colors:
  ink: "#1C2B33"
  sea: "#163E38"
  course: "#9F2D1C"
  course-on-sea: "#F6D2C6"
  chart: "#E7F0EA"
  paper: "#F4F8F5"
  quiet: "#3D534C"
  quiet-on-sea: "#C5DDD4"
  foam: "#D5E6DE"
typography:
  display-latin:
    fontFamily: "Spectral, serif"
    fontSize: "clamp(2.8rem, 7vw, 6rem)"
    fontWeight: 600
    lineHeight: 1.08
    letterSpacing: "-0.03em"
  display-arabic:
    fontFamily: "Amiri, serif"
    fontSize: "clamp(2.8rem, 7vw, 6rem)"
    fontWeight: 700
    lineHeight: 1.12
    letterSpacing: "0em"
  body:
    fontFamily: "Readex Pro, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "0em"
rounded:
  none: "0px"
spacing:
  edge: "1.5rem"
  section: "5rem"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.chart}"
    rounded: "{rounded.none}"
    padding: "0 1.25rem"
    height: "3rem"
  button-primary-on-dark:
    backgroundColor: "{colors.chart}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "0 1.25rem"
    height: "3rem"
  field:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "0 0.8rem"
    height: "3rem"
---

## Overview

RACODE is a bilingual studio site and the desk where its editors publish it. The public pages are one coastal passage: a chart ground, a deep-water field for the opening, and a vermilion course drawn once under the offer. The admin is the same chart, used as a ledger. Arabic is the default direction. English uses the same grid from the other side.

## Colors

The chart ground is `#E7F0EA`. Ink is `#1C2B33`. The footer and other deep fields use `#163E38`. The course, and anything that must be acted on, is `#9F2D1C` on the chart and `#F6D2C6` on the water. Quiet text is `#3D534C` on the chart and `#C5DDD4` on the water. These five public colors are also the brand settings an editor can change: midnight, burgundy, rose, blush, and ivory.

## Typography

English display is Spectral. Arabic display is Amiri. Both languages read in Readex Pro. Display size stops at 6rem. Body copy stays inside about 68 characters. Do not put a small label above a heading.

## Layout

Public pages share a 90rem measure and the same left and right edge. The first screen is the offer, the course, and the departure action. Later sections are indexes ruled in hairlines, not stacks of equal cards. Latitude lines belong to the chart ground. The admin is a 16.5rem ink index beside a ruled sheet.

## Elevation & Depth

Surfaces are flat. A button steps up 2px on hover and presses 1px on click. Photographs sit in a square plate and scale to 1.02 inside that frame. There is no blur, no glass bar, and no drop shadow.

## Shapes

Corners are square. The only drawn curve is the course under a headline. Rules are 1px.

## Components

The slip is the primary action. On water it is chart-colored; on the chart it is ink. Links draw an underline on hover. A sounding is a full-width row with the name and the summary on one baseline; the course draws along its bottom edge on hover. Forms use a paper field with a visible label. The technology line can be paused. Demonstrations stay marked as demonstrations.

## Do's and Don'ts

Do keep Arabic and English on the same structure, each in its own direction. Do keep the course as the only line that draws itself. Do not invent clients, awards, or results. Do not return to equal rounded cards, a pill navigation bar, or a full-bleed photograph behind the headline.
