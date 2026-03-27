/**
 * @typedef {Object} CurrentFormatChoiceValues - The current format choice parameters.
 *
 * @property {"2hg" | "teamtrio" | "duel"} players - Players structure: "2hg", "teamtrio" or "duel".
 *
 * @property {"singleton" | "quadruples"} copies - Copies: "singleton" or "quadruples".
 *
 * @property {"highlander" | "mtg"} structure - Deck structure: "highlander" or "mtg".
 *
 * @property {"yes" | "no"} commander - Using Commanders/Command Zones: "yes" or "no".
 *
 * @property {"printed" | "funny" | "eternal" | "modern" | "pioneer" | "standard"} timeline - The maximum timeline to check against: broader is ok, narrower is not.
 *
 * @property {"standard" | "lite" | "power"} pvalue - The primary value to check against: standard, lite, or power.
 *
 * @property {"yes" | "no"} mvalue - The "yes" or "no" choice for the "M-value" check.
 */

/**
 * @typedef {Object} DeckCard - A deck card.
 *
 * @property {string} name - Deck card name, mostly in English.
 *
 * @property {string} image - Deck card image URL.
 *
 * @property {int} mv - Mana value of the card.
 *
 * @property {string} types - The card types.
 *
 * @property {boolean} czeligible - True if the card can be a Commander, false otherwise.
 *
 * @property {string} multicztype - The multiple commander/zone type.
 *
 * @property {boolean} multiczeligible - True if the card allows multiple commanders, false otherwise.
 *
 * @property {string} timeline - The maximum timeline to check against.
 *
 * @property {array} ci - An array of strings representing the color identity of the card.
 *
 * @property {boolean} b - True if the card is blue, false otherwise.
 *
 * @property {boolean} r - True if the card is red, false otherwise.
 *
 * @property {boolean} w - True if the card is white, false otherwise.
 *
 * @property {boolean} u - True if the card is black, false otherwise.
 *
 * @property {boolean} g - True if the card is green, false otherwise.
 *
 * @property {boolean} c - True if the card is colorless, false otherwise.
 *
 * @property {int} maxcopies - Maximum number of copies of the card in a deck.
 *
 * @property {int} firstprintedyear - The year the card was first printed.
 *
 * @property {int} firstprintedon - The timestamp of when the card was first printed.
 *
 * @property {boolean} legal2HG - True if the card is legal in Two-Headed Giant, false otherwise.
 *
 * @property {boolean} legal2HGSpecial - True if the card is legal in Two-Headed Giant as a Commander, false otherwise.
 *
 * @property {boolean} legalDC - True if the card is legal in Duel Commander, false otherwise.
 *
 * @property {boolean} legalDCSpecial - True if the card is legal in Duel Commander as a Commander, false otherwise.
 *
 * @property {boolean} legalCEDH - True if the card is legal in Commander/Edh, false otherwise.
 *
 * @property {boolean} legalCEDHSpecial - True if the card is legal in Commander/Edh as a Commander, false otherwise.
 */
