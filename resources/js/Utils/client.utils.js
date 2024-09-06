export const leadStatusColor = (_status) => {
    let _color = "";
    switch (_status) {
        case "pending":
            _color = "#ffa500c9";
            break;

        case "potential":
            _color = "lightblue";
            break;

        case "irrelevant":
            _color = "orange";
            break;

        case "uninterested":
            _color = "red";
            break;

        case "unanswered":
            _color = "purple";
            break;

        case "potential client":
            _color = "pink";
            break;

        case "pending client":
            _color = "purple";
            break;

        case "freeze client":
            _color = "#b98787";
            break;

        case "active client":
            _color = "green";
            break;

        case "past":
            _color = "black";
            break;

        case "approved":
            _color = "green";
            break;

        case "rejected":
            _color = "#ff0000b0";
            break;    

        case "active":
            _color = "#1675e0";
            break; 

        case "paid":
            _color = "green";
            break;    
                
        default:
            break;
    }

    return { backgroundColor: _color };
};
