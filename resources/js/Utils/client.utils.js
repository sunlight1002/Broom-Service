export const leadStatusColor = (_status) => {
    let _color = "";
    console.log("Status Color", _status);
    switch (_status?.toLowerCase() || '') {
        case "pending":
            _color = "#ffa500";
            break;

        case "potential":
            _color = "lightblue";
            break;

        case "irrelevant":
            _color = "black";
            break;

        case "uninterested":
            _color = "red";
            break;

        case "unanswered":
            _color = "purple";
            break;

        case "unanswered final":
            _color = "#5d5d5d";
            break;

        case "reschedule call":
            _color = "#5584c7";
            break;

        case "potential client":
            _color = "pink";
            break;

        case "pending client":
            _color = "purple";
            break;

        case "waiting":
            _color = "purple";
            break;

        case "freeze client":
            _color = "#b98787";
            break;

        case "active client":
            _color = "green";
            break;

        case "active":
            _color = "green";
            break;

        case "past":
            _color = "black";
            break;

        case "approved":
            _color = "green";
            break;

        case "verified":
            _color = "green";
            break;

        case "completed":
            _color = "green";
            break;

        case "rejected":
            _color = "#d51212";
            break;

        case "not-signed":
            _color = "#d51212";
            break;

        case "unhappy":
            _color = "red";
            break;

        case "price issue":
            _color = "#770000";
            break;

        case "moved":
            _color = "#4561ab";
            break;

        case "one-time":
            _color = "#626567";
            break;

        case "will-think":
            _color = "#72be6b";
            break;

        case "not-hired":
            _color = "red";
            break;

        case "hiring":
            _color = "green";
            break;

        case "construction visa":
            _color = "#6f6e6e";
            break;

        case "caregiver visa":
            _color = "#e565ac";
            break;

        case "hotel sector":
            _color = "#53bdb3";
            break;

        case "Tied to employer":
            _color = "#4c9b2485";
            break;

        case "expired":
            _color = "#a95a3cf2";
            break;

        case "Not respond to bot":
            _color = "#a6a99df2";
            break;

        case "Not respond to messages":
            _color = "#a95a3cf2";
            break;

        case "voice bot":
            _color = "#a95a3cf2";
            break;

        case "agriculture visa":
            _color = "#ff9871f2";
            break;

        case "other":
            _color = "#456f63f2";
            break;

        default:
            _color = "#6c757d"; // Default gray color for unknown statuses
            break;
    }

    return { backgroundColor: _color };
};
